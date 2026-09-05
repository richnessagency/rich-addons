# RichAddons (`richnessagency/rich-addons`)

A modular plugin and add-on architecture for Laravel applications, inspired by WordPress hooks and plugin architecture. Designed for in-house modular extension with licensing support, action/filter hooks, manifest management, and admin UI integration.

## Features

- 🔌 **Modular Architecture**: Load add-ons seamlessly from local directories or package namespaces.
- 🎣 **WordPress-style Hooks**: Priority-sorted `Actions` (side effects) and `Filters` (pipeline transformations).
- 🏷️ **Pricing Tiers**: Free, Paid, and Subscription tier support (`AddonTier`).
- 🔒 **Licensing & Anti-Piracy Integration**: Flexible `LicenseVerifier` contract and enforcement mechanism.
- 📜 **Manifest Validation**: Parse and validate `addon.json` manifests using typed `AddonManifest` DTOs.
- 🎛️ **Extensible Contracts**: `HasAdminPanel`, `HasHooks`, `HasStorefrontWidgets`, and `Addon` interfaces.

## Installation

```bash
composer require richnessagency/rich-addons
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag="rich-addons-config"
```

## Basic Usage

### Defining an Add-on

```php
use Richness\RichAddons\Contracts\Addon;
use Richness\RichAddons\Data\AddonTier;

class MyAddon implements Addon
{
    public function id(): string
    {
        return 'my-custom-addon';
    }

    public function name(): string
    {
        return 'My Custom Addon';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function tier(): AddonTier
    {
        return AddonTier::Free;
    }

    public function boot(): void
    {
        // Boot logic
    }
}
```

### Using Hooks

```php
use Richness\RichAddons\Hooks\HookManager;

// Adding actions & filters
app(HookManager::class)->addAction('order.created', function ($order) {
    // Handle order creation
}, priority: 10);

app(HookManager::class)->addFilter('product.price', function ($price) {
    return $price * 0.9; // 10% discount
}, priority: 5);

// Executing actions & filters
app(HookManager::class)->doAction('order.created', $order);
$finalPrice = app(HookManager::class)->applyFilters('product.price', 100);
```

## License

MIT License. Created by [Richness Agency](https://github.com/richnessagency).
