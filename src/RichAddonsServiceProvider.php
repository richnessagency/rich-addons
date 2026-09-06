<?php

declare(strict_types=1);

namespace Richness\RichAddons;

use Illuminate\Support\Facades\Route;

use Illuminate\Support\ServiceProvider;
use Richness\RichAddons\Hooks\HookManager;
use Richness\RichAddons\Http\Controllers\Admin\AddonController;
use Richness\RichAddons\Kernel\AddonKernel;
use Richness\RichAddons\Marketplace\CentralMarketplaceClient;
use Richness\RichAddons\Release\HashSignatureVerifier;
use Richness\RichAddons\Release\ReleaseSignatureVerifier;

class RichAddonsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/rich-addons.php',
            'rich-addons'
        );

        $this->app->singleton(HookManager::class, function () {
            return new HookManager();
        });

        $this->app->singleton(\Richness\RichAddons\Contracts\LicenseVerifier::class, function () {
            return new \Richness\RichAddons\Licensing\CryptographicLicenseVerifier();
        });

        $this->app->singleton(AddonKernel::class, function ($app) {
            return new AddonKernel(
                $app->make(HookManager::class),
                $app->make(\Richness\RichAddons\Contracts\LicenseVerifier::class)
            );
        });

        $this->app->singleton(CentralMarketplaceClient::class);
        $this->app->singleton(ReleaseSignatureVerifier::class, HashSignatureVerifier::class);

        $this->app->alias(HookManager::class, 'rich-addons.hooks');
        $this->app->alias(AddonKernel::class, 'rich-addons.kernel');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'rich-addons');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/rich-addons.php' => config_path('rich-addons.php'),
            ], 'rich-addons-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'rich-addons-migrations');

            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }

        $this->registerAdminRoutes();

        // Boot all currently active add-ons
        /** @var AddonKernel $kernel */
        $kernel = $this->app->make(AddonKernel::class);
        $kernel->bootActiveAddons();
    }

    protected function registerAdminRoutes(): void
    {
        Route::prefix((string) config('rich-addons.admin_route_prefix', 'admin/addons'))
            ->middleware((array) config('rich-addons.admin_middleware', ['web']))
            ->name('admin.addons.')
            ->group(function (): void {
                Route::get('/', [AddonController::class, 'index'])->name('index');
                Route::post('/marketplace/refresh', [AddonController::class, 'refreshMarketplace'])->name('marketplace.refresh');
                Route::post('/{addonId}/install', [AddonController::class, 'install'])
                    ->where('addonId', '.*')
                    ->name('install');
                Route::post('/{addonId}/toggle', [AddonController::class, 'toggle'])
                    ->where('addonId', '.*')
                    ->name('toggle');
                Route::post('/{addonId}/license', [AddonController::class, 'updateLicense'])
                    ->where('addonId', '.*')
                    ->name('license');
            });
    }
}
