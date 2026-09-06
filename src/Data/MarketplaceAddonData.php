<?php

declare(strict_types=1);

namespace Richness\RichAddons\Data;

final readonly class MarketplaceAddonData
{
    /**
     * @param array<string, mixed> $release
     * @param list<string> $permissions
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public string $version,
        public AddonTier $tier,
        public string $status,
        public ?string $price,
        public ?string $currency,
        public ?string $billingCycle,
        public ?string $author,
        public array $release,
        public array $permissions,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? $data['addon_id'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            version: (string) ($data['version'] ?? data_get($data, 'release.version', '1.0.0')),
            tier: AddonTier::tryFrom((string) ($data['tier'] ?? 'free')) ?? AddonTier::Free,
            status: (string) ($data['status'] ?? 'available'),
            price: isset($data['price']) ? (string) $data['price'] : null,
            currency: isset($data['currency']) ? (string) $data['currency'] : null,
            billingCycle: isset($data['billing_cycle']) ? (string) $data['billing_cycle'] : null,
            author: isset($data['author']) ? (string) $data['author'] : null,
            release: (array) ($data['release'] ?? []),
            permissions: array_values(array_filter((array) ($data['permissions'] ?? []), 'is_string')),
        );
    }
}
