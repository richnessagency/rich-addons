<?php

declare(strict_types=1);

namespace Richness\RichAddons\Marketplace;

use Illuminate\Support\Facades\File;
use Richness\RichAddons\Data\ReleasePackageMetadata;
use Richness\RichAddons\Models\AddonModel;
use Richness\RichAddons\Release\SignedReleaseInstaller;

class InstallMarketplaceAddon
{
    public function __construct(
        protected SignedReleaseInstaller $installer,
    ) {}

    public function handle(AddonModel $addon): AddonModel
    {
        $release = (array) data_get($addon->manifest ?? [], 'release', []);
        $metadata = ReleasePackageMetadata::fromArray([
            'addon_id' => $addon->addon_id,
            'version' => $release['version'] ?? $addon->version,
            'download_url' => $release['download_url'] ?? '',
            'checksum' => $release['checksum'] ?? $addon->release_checksum,
            'signature' => $release['signature'] ?? $addon->release_signature,
            'composer_package' => $release['composer_package'] ?? null,
        ]);

        if ($metadata->downloadUrl === '') {
            throw new \RuntimeException('Marketplace add-on does not have a signed release download URL.');
        }

        $stagedPath = $this->installer->install($metadata);
        $installPath = $this->installPath($addon);

        if (File::exists($installPath)) {
            File::deleteDirectory($installPath);
        }

        File::ensureDirectoryExists(dirname($installPath));
        File::moveDirectory($stagedPath, $installPath, true);

        $addon->forceFill([
            'installed_path' => $installPath,
            'staged_path' => null,
            'installed_at' => now(),
            'status' => 'inactive',
            'failure_reason' => null,
        ])->save();

        return $addon;
    }

    protected function installPath(AddonModel $addon): string
    {
        $directory = str_replace(['/', '\\'], '-', $addon->addon_id);

        return rtrim((string) config('rich-addons.addons_path'), '/') . '/' . $directory;
    }
}
