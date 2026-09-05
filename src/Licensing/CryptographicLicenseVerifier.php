<?php

declare(strict_types=1);

namespace Richness\RichAddons\Licensing;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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

        // 1. Check local cached payload (No HTTP request!)
        $cachedPayload = Cache::get($cacheKey);
        if ($cachedPayload && is_array($cachedPayload)) {
            if ($this->verifySignatureLocally($cachedPayload, $hostDomain)) {
                return new LicenseResult(true, 'تم التحقق من الترخيص محلياً من الكاش.');
            }
        }

        // 2. Lazy On-Demand Check to MainSite Server (Throttled & Cached for 72 Hours)
        try {
            $response = Http::timeout(3)
                ->asJson()
                ->post(rtrim($this->serverUrl, '/') . '/api/v1/licenses/ping', [
                    'license_key' => $licenseKey,
                    'addon_id' => $manifest->id,
                    'domain' => $hostDomain,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $payload = $data['payload'] ?? [];

                if ($data['success'] === true && $this->verifySignatureLocally($payload, $hostDomain)) {
                    // Cache verified token for 72 hours (3 days)
                    Cache::put($cacheKey, $payload, now()->addHours(72));

                    return new LicenseResult(true, 'تم التحقق وتجديد الترخيص من السيرفر الرئيسي.');
                }

                return new LicenseResult(false, $data['message'] ?? 'فشل التحقق من صحة الترخيص.');
            }
        } catch (\Throwable $e) {
            logger()->warning("License server ping failed: " . $e->getMessage());
        }

        // 3. Fallback: Grace Period Check if Server is Offline / Unreachable
        if ($cachedPayload && ! empty($cachedPayload['grace_until'])) {
            $graceUntil = \Illuminate\Support\Carbon::parse($cachedPayload['grace_until']);
            if ($graceUntil->isFuture()) {
                return new LicenseResult(
                    true,
                    'سيرفر التراخيص غير متاح، الإضافة تعمل في فترة السماح (Grace Period).'
                );
            }
        }

        return new LicenseResult(false, 'تعذر الوصول لسيرفر التراخيص وانقضت فترة السماح.');
    }

    /**
     * Local signature and domain binding check.
     */
    protected function verifySignatureLocally(array $payload, string $hostDomain): bool
    {
        if (empty($payload['signature']) || empty($payload['domain'])) {
            return false;
        }

        $signature = $payload['signature'];
        $payloadData = $payload;
        unset($payloadData['signature']);

        $secret = config('rich-addons.secret_key', config('app.key'));
        $expected = hash_hmac('sha256', json_encode($payloadData, JSON_UNESCAPED_SLASHES), $secret);

        if (! hash_equals($expected, $signature)) {
            return false;
        }

        // Domain binding check
        $payloadDomain = strtolower(parse_url($payload['domain'], PHP_URL_HOST) ?? $payload['domain']);
        $currentDomain = strtolower(parse_url($hostDomain, PHP_URL_HOST) ?? $hostDomain);

        return $payloadDomain === $currentDomain || str_contains($currentDomain, $payloadDomain);
    }
}
