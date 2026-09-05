<?php

declare(strict_types=1);

namespace Richness\RichAddons\Kernel;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Richness\RichAddons\Contracts\Addon;
use Richness\RichAddons\Contracts\HasAdminPanel;
use Richness\RichAddons\Contracts\HasHooks;
use Richness\RichAddons\Contracts\HasStorefrontWidgets;
use Richness\RichAddons\Contracts\LicenseVerifier;
use Richness\RichAddons\Data\AddonManifest;
use Richness\RichAddons\Data\AddonStatus;
use Richness\RichAddons\Data\AddonTier;
use Richness\RichAddons\Hooks\HookManager;
use Richness\RichAddons\Models\AddonModel;

class AddonKernel
{
    /** @var array<string, Addon> Loaded active addon instances */
    protected array $loadedAddons = [];

    public function __construct(
        protected HookManager $hookManager,
        protected ?LicenseVerifier $licenseVerifier = null
    ) {}

    /**
     * Discover all local add-ons in the configured directories and sync with database.
     *
     * @return Collection<int, AddonModel>
     */
    public function discover(): Collection
    {
        $directory = config('rich-addons.directory', base_path('addons'));

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $addonFolders = File::directories($directory);

        foreach ($addonFolders as $folder) {
            $manifestPath = $folder . '/addon.json';
            if (! File::exists($manifestPath)) {
                continue;
            }

            try {
                $content = json_decode(File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
                $manifest = AddonManifest::fromArray($content);

                AddonModel::updateOrCreate(
                    ['addon_id' => $manifest->id],
                    [
                        'name' => $manifest->name,
                        'description' => $manifest->description,
                        'version' => $manifest->version,
                        'author' => $manifest->author,
                        'repository' => $manifest->repository,
                        'tier' => $manifest->tier,
                        'manifest' => $content,
                        'installed_path' => $folder,
                    ]
                );
            } catch (\Throwable $e) {
                logger()->error("Failed to parse addon manifest at {$manifestPath}: " . $e->getMessage());
            }
        }

        return AddonModel::all();
    }

    /**
     * Toggle the active status of an add-on.
     */
    public function toggle(string $addonId): bool
    {
        $record = AddonModel::where('addon_id', $addonId)->firstOrFail();

        if ($record->isActive()) {
            $record->deactivate();
        } else {
            // Verify license if required
            if ($record->tier->requiresLicense() && $this->licenseVerifier !== null) {
                $manifestPath = ($record->installed_path ?? '') . '/addon.json';
                $manifestData = File::exists($manifestPath)
                    ? json_decode(File::get($manifestPath), true) ?? []
                    : ($record->manifest ?? []);
                $manifest = AddonManifest::fromArray($manifestData);

                $result = $this->licenseVerifier->verify($record->license_key ?? '', $manifest, $record);
                if (! $result->valid) {
                    throw new \RuntimeException($result->message ?: "License verification failed for {$record->name}");
                }
            }

            $record->activate();
        }

        Cache::forget('rich_addons.active_list');

        return $record->isActive();
    }

    /**
     * Boot all active add-ons.
     */
    public function bootActiveAddons(): void
    {
        $activeRecords = Cache::remember('rich_addons.active_list', 3600, function () {
            return AddonModel::where('status', AddonStatus::Active->value)->get()->toArray();
        });

        foreach ($activeRecords as $record) {
            $manifestData = is_array($record['manifest'] ?? null)
                ? $record['manifest']
                : (json_decode($record['manifest'] ?? '{}', true) ?: []);

            if (empty($manifestData)) {
                continue;
            }

            $manifest = AddonManifest::fromArray($manifestData);
            $mainClass = $manifest->mainClass;
            $installedPath = $record['installed_path'] ?? '';
            $addonId = $record['addon_id'] ?? '';

            // AutoloadPSR-4 from local directory if present
            if ($installedPath && ! empty($manifest->psr4)) {
                foreach ($manifest->psr4 as $prefix => $path) {
                    $srcPath = rtrim($installedPath, '/') . '/' . ltrim($path, '/');
                    $this->registerPsr4Autoloader($prefix, $srcPath);
                }
            }

            if (! class_exists($mainClass)) {
                continue;
            }

            try {
                /** @var Addon $addonInstance */
                $addonInstance = app()->make($mainClass);
                $addonInstance->boot();

                if ($addonInstance instanceof HasHooks) {
                    $addonInstance->registerHooks($this->hookManager);
                }

                if ($addonInstance instanceof HasAdminPanel) {
                    $addonInstance->registerAdminRoutes();
                }

                $this->loadedAddons[$addonId] = $addonInstance;
            } catch (\Throwable $e) {
                logger()->error("Error booting addon [{$addonId}]: " . $e->getMessage());
            }
        }
    }

    /**
     * PSR-4 dynamic autoloader for un-compiled local add-ons.
     */
    protected function registerPsr4Autoloader(string $prefix, string $baseDir): void
    {
        spl_autoload_register(function (string $class) use ($prefix, $baseDir): void {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            $relativeClass = substr($class, $len);
            $file = rtrim($baseDir, '/') . '/' . str_replace('\\', '/', $relativeClass) . '.php';

            if (File::exists($file)) {
                require_once $file;
            }
        });
    }

    /**
     * Get all currently booted add-on instances.
     *
     * @return array<string, Addon>
     */
    public function getLoadedAddons(): array
    {
        return $this->loadedAddons;
    }
}
