<?php

declare(strict_types=1);

namespace Richness\RichAddons\Data;

final readonly class ReleasePackageMetadata
{
    public function __construct(
        public string $addonId,
        public string $version,
        public string $downloadUrl,
        public string $checksum,
        public string $signature,
        public ?string $composerPackage = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            addonId: (string) ($data['addon_id'] ?? $data['id'] ?? ''),
            version: (string) ($data['version'] ?? ''),
            downloadUrl: (string) ($data['download_url'] ?? $data['signed_zip_url'] ?? ''),
            checksum: (string) ($data['checksum'] ?? ''),
            signature: (string) ($data['signature'] ?? ''),
            composerPackage: isset($data['composer_package']) ? (string) $data['composer_package'] : null,
        );
    }
}
