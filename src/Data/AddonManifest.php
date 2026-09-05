<?php

declare(strict_types=1);

namespace Richness\RichAddons\Data;

final readonly class AddonManifest
{
    /**
     * @param  list<string>  $permissions
     * @param  list<string>  $hooks
     */
    public function __construct(
        public string $identifier,
        public string $name,
        public ?string $nameEn,
        public string $version,
        public string $author,
        public string $description,
        public AddonTier $tier,
        public ?string $minAppVersion,
        public string $provider,
        public array $permissions,
        public array $hooks,
        public string $icon,
        public ?string $checksum,
        public string $basePath,
        public array $psr4 = [],
    ) {}

    /**
     * Parse an array into an AddonManifest DTO.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, string $basePath = ''): self
    {
        $id = (string) ($data['id'] ?? $data['identifier'] ?? 'unknown');

        return new self(
            identifier: $id,
            name: (string) ($data['name'] ?? ''),
            nameEn: isset($data['name_en']) ? (string) $data['name_en'] : null,
            version: (string) ($data['version'] ?? '1.0.0'),
            author: (string) ($data['author'] ?? 'Unknown'),
            description: (string) ($data['description'] ?? ''),
            tier: AddonTier::tryFrom((string) ($data['tier'] ?? 'free')) ?? AddonTier::Free,
            minAppVersion: isset($data['min_app_version']) ? (string) $data['min_app_version'] : null,
            provider: (string) ($data['mainClass'] ?? $data['provider'] ?? ''),
            permissions: array_values(array_filter((array) ($data['permissions'] ?? []), 'is_string')),
            hooks: array_values(array_filter((array) ($data['hooks'] ?? []), 'is_string')),
            icon: (string) ($data['icon'] ?? 'fa-solid fa-puzzle-piece'),
            checksum: isset($data['checksum']) ? (string) $data['checksum'] : null,
            basePath: (string) ($data['base_path'] ?? $basePath),
            psr4: (array) ($data['psr4'] ?? []),
        );
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'id' => $this->identifier,
            'repository' => $this->checksum,
            'mainClass' => $this->provider,
            'psr4' => $this->psr4,
            default => null,
        };
    }

    /**
     * Parse an addon.json file into a manifest DTO.
     *
     * @throws \InvalidArgumentException
     */
    public static function fromJsonFile(string $jsonPath): self
    {
        if (! file_exists($jsonPath)) {
            throw new \InvalidArgumentException("Addon manifest not found: {$jsonPath}");
        }

        $raw = file_get_contents($jsonPath);

        if ($raw === false) {
            throw new \InvalidArgumentException("Cannot read addon manifest: {$jsonPath}");
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);

        return self::fromArray($data, dirname($jsonPath));
    }

    /**
     * Serialize back to an array (for storing in DB JSON column).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'name' => $this->name,
            'name_en' => $this->nameEn,
            'version' => $this->version,
            'author' => $this->author,
            'description' => $this->description,
            'tier' => $this->tier->value,
            'min_app_version' => $this->minAppVersion,
            'provider' => $this->provider,
            'permissions' => $this->permissions,
            'hooks' => $this->hooks,
            'icon' => $this->icon,
            'checksum' => $this->checksum,
            'base_path' => $this->basePath,
        ];
    }
}
