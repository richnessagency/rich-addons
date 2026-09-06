<?php

declare(strict_types=1);

namespace Richness\RichAddons\Release;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\File;
use Richness\RichAddons\Data\ReleasePackageMetadata;
use ZipArchive;

class SignedReleaseInstaller
{
    public function __construct(
        protected HttpFactory $http,
        protected ReleaseSignatureVerifier $verifier,
    ) {}

    public function install(ReleasePackageMetadata $metadata): string
    {
        $archivePath = $this->download($metadata);

        if (! $this->verifier->verifyFile($archivePath, $metadata->checksum, $metadata->signature)) {
            File::delete($archivePath);

            throw new \RuntimeException('Release signature or checksum verification failed.');
        }

        $stagedPath = $this->stagedPath($metadata);

        if (File::exists($stagedPath)) {
            File::deleteDirectory($stagedPath);
        }

        File::ensureDirectoryExists($stagedPath);
        $this->extract($archivePath, $stagedPath);
        File::delete($archivePath);

        $this->validateManifest($stagedPath, $metadata);

        return $stagedPath;
    }

    protected function download(ReleasePackageMetadata $metadata): string
    {
        $temporaryPath = storage_path('app/rich-addons/downloads/' . md5($metadata->addonId . $metadata->version) . '.zip');
        File::ensureDirectoryExists(dirname($temporaryPath));

        if (str_starts_with($metadata->downloadUrl, 'file://')) {
            $source = substr($metadata->downloadUrl, 7);

            if (! is_file($source)) {
                throw new \RuntimeException('Release archive file was not found.');
            }

            File::copy($source, $temporaryPath);

            return $temporaryPath;
        }

        $response = $this->http
            ->timeout((int) config('rich-addons.request_timeout_seconds', 5))
            ->withHeaders([
                'X-Rich-Addons-System' => (string) config('rich-addons.system_key', ''),
            ])
            ->when((string) config('rich-addons.system_secret', '') !== '', function ($request) {
                return $request->withToken((string) config('rich-addons.system_secret', ''));
            })
            ->get($metadata->downloadUrl);

        if (! $response->successful()) {
            throw new \RuntimeException('Release download failed with status ' . $response->status());
        }

        File::put($temporaryPath, $response->body());

        return $temporaryPath;
    }

    protected function extract(string $archivePath, string $stagedPath): void
    {
        $archive = new ZipArchive();

        if ($archive->open($archivePath) !== true) {
            throw new \RuntimeException('Unable to open release archive.');
        }

        try {
            $this->validateArchiveEntries($archive);
            $archive->extractTo($stagedPath);
        } finally {
            $archive->close();
        }
    }

    protected function validateArchiveEntries(ZipArchive $archive): void
    {
        for ($index = 0; $index < $archive->numFiles; $index++) {
            $name = $archive->getNameIndex($index);

            if (! is_string($name)) {
                throw new \RuntimeException('Release archive contains an unreadable entry.');
            }

            $normalized = str_replace('\\', '/', $name);

            if (
                str_starts_with($normalized, '/')
                || preg_match('/^[A-Za-z]:\//', $normalized) === 1
                || str_contains($normalized, '../')
                || str_contains($normalized, '/..')
                || $normalized === '..'
            ) {
                throw new \RuntimeException('Release archive contains an unsafe file path.');
            }
        }
    }

    protected function validateManifest(string $stagedPath, ReleasePackageMetadata $metadata): void
    {
        $manifestPath = $stagedPath . '/addon.json';

        if (! File::exists($manifestPath)) {
            throw new \RuntimeException('Release archive does not contain addon.json.');
        }

        $manifest = json_decode(File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $addonId = (string) ($manifest['id'] ?? $manifest['identifier'] ?? '');
        $version = (string) ($manifest['version'] ?? '');

        if ($addonId !== $metadata->addonId) {
            throw new \RuntimeException('Release manifest add-on id does not match the selected add-on.');
        }

        if ($version !== $metadata->version) {
            throw new \RuntimeException('Release manifest version does not match the selected release.');
        }
    }

    protected function stagedPath(ReleasePackageMetadata $metadata): string
    {
        $directory = str_replace(['/', '\\'], '-', $metadata->addonId);

        return rtrim((string) config('rich-addons.staging_path'), '/') . '/' . $directory . '-' . $metadata->version;
    }
}
