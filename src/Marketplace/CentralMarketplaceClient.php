<?php

declare(strict_types=1);

namespace Richness\RichAddons\Marketplace;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Request;

class CentralMarketplaceClient
{
    public function __construct(
        protected HttpFactory $http,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function catalog(): array
    {
        $response = $this->request()
            ->get($this->url('/api/v1/addons/catalog'), [
                'domain' => Request::getHost(),
                'runtime_version' => $this->runtimeVersion(),
            ]);

        return $this->decode($response);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function startCheckout(string $addonId, array $payload): array
    {
        $response = $this->request()
            ->post($this->url("/api/v1/addons/{$addonId}/checkout"), $payload + [
                'domain' => Request::getHost(),
            ]);

        return $this->decode($response);
    }

    /**
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function request(): mixed
    {
        $systemKey = (string) config('rich-addons.system_key', '');
        $systemSecret = (string) config('rich-addons.system_secret', '');

        $request = $this->http
            ->timeout((int) config('rich-addons.request_timeout_seconds', 5))
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'X-Rich-Addons-System' => $systemKey,
            ]);

        if ($systemSecret !== '') {
            $request = $request->withToken($systemSecret);
        }

        return $request;
    }

    protected function url(string $path): string
    {
        return rtrim((string) config('rich-addons.marketplace_url'), '/') . '/' . ltrim($path, '/');
    }

    protected function runtimeVersion(): string
    {
        return '1.0.0';
    }

    /**
     * @return array<string, mixed>
     */
    protected function decode(Response $response): array
    {
        if (! $response->successful()) {
            throw new \RuntimeException('Marketplace request failed with status ' . $response->status());
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new \RuntimeException('Marketplace response was not valid JSON.');
        }

        return $payload;
    }
}
