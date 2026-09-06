<?php

declare(strict_types=1);

namespace Richness\RichAddons\Licensing;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use Richness\RichAddons\Contracts\LicenseVerifier;
use Richness\RichAddons\Data\AddonManifest;
use Richness\RichAddons\Data\LicenseResult;
use Richness\RichAddons\Models\AddonModel;

class CryptographicLicenseVerifier implements LicenseVerifier
{
    protected string $serverUrl;

    public function __construct(?string $serverUrl = null)
    {
        $this->serverUrl = $serverUrl ?: config('rich-addons.license_server_url', 'https://richnessagency.com');
    }

    public function verify(string $licenseKey, AddonManifest $manifest, AddonModel $record): LicenseResult
    {
        if (empty($licenseKey)) {
            return new LicenseResult(false, 'مفتاح الترخيص مطلوبة لهذه الإضافة المدفوعة.');
        }

        $hostDomain = request()->getHost();
        $cacheKey = "rich_addons.license." . md5($licenseKey . $manifest->id . $hostDomain);
        $storedPayload = is_array($record->license_payload) ? $record->license_payload : null;

        foreach ([$storedPayload, Cache::get($cacheKey)] as $cachedPayload) {
            if (is_array($cachedPayload) && $this->verifyPayload($cachedPayload, $hostDomain, $manifest->id, $licenseKey)) {
                if (! $this->payloadIsExpired($cachedPayload)) {
                    Cache::put($cacheKey, $cachedPayload, now()->addHours(72));

                    return new LicenseResult(true, 'تم التحقق من الترخيص محلياً من الكاش.');
                }

                if ($this->payloadIsInGrace($cachedPayload)) {
                    return new LicenseResult(true, 'الترخيص في فترة السماح.');
                }
            }
        }

        // 2. Lazy On-Demand Check to MainSite Server (Throttled & Cached for 72 Hours)
        try {
            $response = $this->request()
                ->post(rtrim($this->serverUrl, '/') . '/api/v1/addons/licenses/ping', [
                    'license_key' => $licenseKey,
                    'addon_id' => $manifest->id,
                    'domain' => $hostDomain,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $payload = is_array($data['payload'] ?? null) ? $data['payload'] : [];

                if (($data['success'] ?? false) === true && $this->verifyPayload($payload, $hostDomain, $manifest->id, $licenseKey)) {
                    $record->forceFill([
                        'license_payload' => $payload,
                        'last_license_result' => $data,
                        'failure_reason' => null,
                    ])->save();

                    Cache::put($cacheKey, $payload, now()->addHours(72));

                    return new LicenseResult(true, 'تم التحقق وتجديد الترخيص من السيرفر الرئيسي.', $payload['expires_at'] ?? null);
                }

                $record->forceFill([
                    'last_license_result' => $data,
                    'failure_reason' => $data['message'] ?? 'فشل التحقق من صحة الترخيص.',
                ])->save();

                return new LicenseResult(false, $data['message'] ?? 'فشل التحقق من صحة الترخيص.');
            }
        } catch (\Throwable $e) {
            logger()->warning("License server ping failed: " . $e->getMessage());
        }

        foreach ([$storedPayload, Cache::get($cacheKey)] as $cachedPayload) {
            if (
                is_array($cachedPayload)
                && $this->verifyPayload($cachedPayload, $hostDomain, $manifest->id, $licenseKey)
                && $this->payloadIsInGrace($cachedPayload)
            ) {
                return new LicenseResult(true, 'تم التحقق من الترخيص محلياً من الكاش.');
            }
        }

        return new LicenseResult(false, 'تعذر الوصول لسيرفر التراخيص وانقضت فترة السماح.');
    }

    public function activate(AddonModel $record, AddonManifest $manifest): LicenseResult
    {
        $licenseKey = (string) ($record->license_key ?? '');

        if ($licenseKey === '') {
            return new LicenseResult(false, 'مفتاح الترخيص مطلوبة لهذه الإضافة المدفوعة.');
        }

        $hostDomain = request()->getHost();

        try {
            $response = $this->request()
                ->post(rtrim($this->serverUrl, '/') . '/api/v1/addons/licenses/activate', [
                    'license_key' => $licenseKey,
                    'addon_id' => $manifest->id,
                    'domain' => $hostDomain,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $payload = is_array($data['payload'] ?? null) ? $data['payload'] : [];

                if (($data['success'] ?? false) === true && $this->verifyPayload($payload, $hostDomain, $manifest->id, $licenseKey)) {
                    $record->forceFill([
                        'license_payload' => $payload,
                        'last_license_result' => $data,
                        'failure_reason' => null,
                    ])->save();

                    Cache::put("rich_addons.license." . md5($licenseKey . $manifest->id . $hostDomain), $payload, now()->addHours(72));

                    return new LicenseResult(true, 'تم تفعيل الترخيص من السيرفر الرئيسي.', $payload['expires_at'] ?? null);
                }

                return new LicenseResult(false, $data['message'] ?? 'فشل التحقق من صحة الترخيص.');
            }
        } catch (\Throwable $e) {
            logger()->warning("License server activation failed: " . $e->getMessage());
        }

        return new LicenseResult(false, 'تعذر تفعيل الترخيص من السيرفر الرئيسي.');
    }

    /**
     * Local signature and domain binding check.
     */
    protected function verifyPayload(array $payload, string $hostDomain, string $addonId, string $licenseKey): bool
    {
        if (empty($payload['signature']) || empty($payload['domain'])) {
            return false;
        }

        if (($payload['license_key'] ?? null) !== $licenseKey || ($payload['addon_id'] ?? null) !== $addonId) {
            return false;
        }

        $configuredSystemKey = (string) config('rich-addons.system_key', '');
        if ($configuredSystemKey !== '' && ($payload['system_key'] ?? null) !== $configuredSystemKey) {
            return false;
        }

        $signature = $payload['signature'];
        $payloadData = $payload;
        unset($payloadData['signature']);

        if (! $this->verifySignature($payloadData, (string) $signature, (string) ($payload['signature_alg'] ?? 'hmac-sha256'))) {
            return false;
        }

        $currentDomain = $this->normalizeDomain($hostDomain);
        $allowedDomains = array_values(array_filter((array) ($payload['allowed_domains'] ?? [$payload['domain']]), 'is_string'));

        foreach ($allowedDomains as $allowedDomain) {
            if ($this->domainMatches($currentDomain, $allowedDomain)) {
                return true;
            }
        }

        return false;
    }

    protected function verifySignature(array $payloadData, string $signature, string $algorithm): bool
    {
        $canonical = json_encode($payloadData, JSON_UNESCAPED_SLASHES);

        if ($algorithm === 'openssl-sha256') {
            $publicKey = $this->publicKey();

            return $publicKey !== ''
                && base64_decode($signature, true) !== false
                && openssl_verify((string) $canonical, (string) base64_decode($signature, true), $publicKey, OPENSSL_ALGO_SHA256) === 1;
        }

        $secret = (string) config('rich-addons.secret_key', '');

        return $secret !== ''
            && hash_equals(hash_hmac('sha256', (string) $canonical, $secret), $signature);
    }

    protected function publicKey(): string
    {
        $inline = (string) config('rich-addons.license_public_key', '');
        if ($inline !== '') {
            return str_replace('\\n', "\n", $inline);
        }

        $path = (string) config('rich-addons.public_key_path', '');

        return $path !== '' && is_file($path) ? (string) file_get_contents($path) : '';
    }

    protected function payloadIsExpired(array $payload): bool
    {
        return ! empty($payload['expires_at']) && Carbon::parse((string) $payload['expires_at'])->isPast();
    }

    protected function payloadIsInGrace(array $payload): bool
    {
        return ! empty($payload['grace_until']) && Carbon::parse((string) $payload['grace_until'])->isFuture();
    }

    protected function request(): mixed
    {
        $request = Http::timeout(3)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'X-Rich-Addons-System' => (string) config('rich-addons.system_key', ''),
            ]);

        $systemSecret = (string) config('rich-addons.system_secret', '');

        return $systemSecret !== '' ? $request->withToken($systemSecret) : $request;
    }

    protected function normalizeDomain(string $domain): string
    {
        $domain = trim(strtolower($domain));
        $host = parse_url(str_contains($domain, '://') ? $domain : 'https://' . $domain, PHP_URL_HOST);
        $host = trim((string) ($host ?: $domain), '.');

        return preg_replace('/^www\./', '', $host) ?: '';
    }

    protected function domainMatches(string $currentDomain, string $allowedDomain): bool
    {
        $allowedDomain = $this->normalizeDomain($allowedDomain);

        return $currentDomain === $allowedDomain
            || str_ends_with($currentDomain, '.' . $allowedDomain);
    }
}
