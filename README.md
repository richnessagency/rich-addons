# Rich Addons

`richnessagency/rich-addons` is the shared Laravel add-on runtime used by
Richness applications. It lets a host application discover add-ons, install
signed marketplace releases, activate free or paid modules, register hooks,
expose admin pages, and render storefront widgets.

The package is intended to be installed through Composer, not copied into a
project as local source code.

```bash
composer require richnessagency/rich-addons
```

## What This Package Provides

- Local add-on discovery from `addons/*/addon.json`.
- Marketplace catalog sync from a central Laravel application.
- Signed ZIP release download and installation.
- SHA-256 checksum verification.
- HMAC or OpenSSL release signature verification.
- Paid and subscription license activation.
- Signed license payload validation bound to add-on id, domain, and system key.
- Runtime license enforcement before paid/subscription add-ons are booted.
- Action and filter hooks inspired by WordPress.
- Optional admin routes and storefront widgets.

## Host Application Setup

Install the package:

```bash
composer require richnessagency/rich-addons
```

Publish the config:

```bash
php artisan vendor:publish --tag=rich-addons-config
```

Run migrations:

```bash
php artisan migrate
```

Add the runtime settings to `.env`:

```dotenv
RICH_ADDONS_PATH=addons
RICH_ADDONS_MARKETPLACE_URL=https://your-core-marketplace.test
RICH_ADDONS_LICENSE_SERVER=https://your-core-marketplace.test
RICH_ADDONS_SYSTEM_KEY=client-store-001
RICH_ADDONS_SYSTEM_SECRET=server-issued-system-secret
RICH_ADDONS_RELEASE_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----..."
RICH_ADDONS_LICENSE_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----..."
```

For development or HMAC-only environments you may use:

```dotenv
RICH_ADDONS_SECRET_KEY=shared-development-secret
```

Production should prefer public/private key signing:

- The central system keeps the private key.
- Client applications only receive the public key.
- Client applications must not know the signing secret/private key.

## Configuration Reference

The package config lives in `config/rich-addons.php`.

| Key | Env | Purpose |
| --- | --- | --- |
| `addons_path` | `RICH_ADDONS_PATH` | Directory where installed add-ons live. |
| `auto_discover` | `RICH_ADDONS_AUTO_DISCOVER` | Enables runtime discovery. |
| `admin_route_prefix` | `RICH_ADDONS_ROUTE_PREFIX` | Admin route prefix for add-on management. |
| `marketplace_url` | `RICH_ADDONS_MARKETPLACE_URL` | Central marketplace API base URL. |
| `system_key` | `RICH_ADDONS_SYSTEM_KEY` | Stable id for the consuming system. |
| `system_secret` | `RICH_ADDONS_SYSTEM_SECRET` | Bearer token used with central API calls. |
| `staging_path` | `RICH_ADDONS_STAGING_PATH` | Temporary extraction directory for releases. |
| `release_public_key` | `RICH_ADDONS_RELEASE_PUBLIC_KEY` | Public key for release ZIP signatures. |
| `license_server_url` | `RICH_ADDONS_LICENSE_SERVER` | Central license API base URL. |
| `license_public_key` | `RICH_ADDONS_LICENSE_PUBLIC_KEY` | Public key for license payload signatures. |
| `public_key_path` | `RICH_ADDONS_PUBLIC_KEY` | Path to a public key file. |
| `secret_key` | `RICH_ADDONS_SECRET_KEY` | HMAC fallback secret. |
| `heartbeat_interval_hours` | `RICH_ADDONS_HEARTBEAT_HOURS` | Intended remote validation cadence. |
| `cache_seconds` | `RICH_ADDONS_CACHE_SECONDS` | Local registry cache TTL. |

## Add-on Directory Structure

A local or marketplace-installed add-on should be a self-contained directory:

```text
addons/
  richness-announcement-bar/
    addon.json
    src/
      AnnouncementBarAddon.php
      Http/
        Controllers/
      Models/
      Providers/
    database/
      migrations/
    resources/
      views/
      js/
      css/
    routes/
      admin.php
      web.php
```

The only required file is `addon.json`. The `src` directory and extra folders
depend on what the add-on needs.

## Manifest: `addon.json`

Example manifest:

```json
{
  "id": "richness/announcement-bar",
  "name": "Announcement Bar",
  "name_en": "Announcement Bar",
  "version": "1.0.0",
  "author": "Richness Agency",
  "description": "Adds a configurable announcement bar to the storefront.",
  "tier": "subscription",
  "min_app_version": "1.0.0",
  "provider": "Richness\\AnnouncementBar\\AnnouncementBarAddon",
  "permissions": [
    "settings.read",
    "settings.write"
  ],
  "hooks": [
    "storefront.layout.before_header"
  ],
  "icon": "fa-solid fa-bullhorn",
  "psr4": {
    "Richness\\AnnouncementBar\\": "src/"
  }
}
```

Supported keys:

| Key | Required | Notes |
| --- | --- | --- |
| `id` | yes | Unique machine id. Prefer `vendor/addon-name`. |
| `name` | yes | Display name. |
| `name_en` | no | Optional English display name. |
| `version` | yes | Semantic version. |
| `author` | no | Defaults to `Unknown`. |
| `description` | no | Short purpose statement. |
| `tier` | no | `free`, `paid`, or `subscription`. Defaults to `free`. |
| `min_app_version` | no | Host app compatibility marker. |
| `provider` | yes for bootable add-ons | Main add-on PHP class. |
| `mainClass` | legacy alias | Alias for `provider`. |
| `permissions` | no | Declared capabilities for review/UI. |
| `hooks` | no | Hooks used or provided by the add-on. |
| `icon` | no | Admin UI icon class. |
| `checksum` | marketplace | Release checksum metadata. |
| `psr4` | local add-ons | Runtime autoload mapping for non-Composer add-ons. |

## Main Add-on Class

The main class must implement `Richness\RichAddons\Contracts\Addon`.
The easiest path is extending `AbstractAddon`.

```php
<?php

declare(strict_types=1);

namespace Richness\AnnouncementBar;

use Richness\RichAddons\Data\AddonTier;
use Richness\RichAddons\Support\AbstractAddon;

final class AnnouncementBarAddon extends AbstractAddon
{
    public function id(): string
    {
        return 'richness/announcement-bar';
    }

    public function name(): string
    {
        return 'Announcement Bar';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function description(): string
    {
        return 'Adds a configurable storefront announcement bar.';
    }

    public function tier(): AddonTier
    {
        return AddonTier::Subscription;
    }

    public function install(): void
    {
        // Create tables, seed settings, or publish assets if needed.
    }

    public function boot(): void
    {
        // Register bindings, view namespaces, config defaults, or lightweight services.
    }

    public function uninstall(): void
    {
        // Remove add-on-owned data only when the admin explicitly uninstalls it.
    }
}
```

Keep `boot()` lightweight. Expensive jobs should be queued, cached, or executed
from explicit admin actions.

## Backend Integration

Use normal Laravel code inside the add-on:

- Controllers for admin or public actions.
- Form requests for validation.
- Policies/gates for authorization.
- Jobs/listeners for async work.
- Models for add-on-owned tables.
- Config defaults inside the add-on class or a service provider.

An add-on should not edit host application core files. Integration should happen
through hooks, service container bindings, routes, views, migrations, and
published configuration.

## Database Integration

Place migrations under the add-on directory:

```text
database/
  migrations/
    2026_09_07_000000_create_announcement_bars_table.php
```

Recommended rules:

- Prefix add-on-owned tables, for example `addon_announcement_bars`.
- Keep foreign keys explicit and nullable only when the relationship is optional.
- Avoid changing host tables unless the host application exposes a documented
  extension point.
- Migrations must be idempotent where possible by checking existing tables or
  columns.
- `uninstall()` must never delete customer data silently. Destructive cleanup
  should require a clear admin action.

Example:

```php
Schema::create('addon_announcement_bars', function (Blueprint $table): void {
    $table->id();
    $table->string('message');
    $table->boolean('is_enabled')->default(true);
    $table->timestamp('starts_at')->nullable();
    $table->timestamp('ends_at')->nullable();
    $table->timestamps();
});
```

## Hooks

Add-ons can register action and filter listeners by implementing `HasHooks`.

```php
use Richness\RichAddons\Contracts\HasHooks;
use Richness\RichAddons\Hooks\HookManager;

final class AnnouncementBarAddon extends AbstractAddon implements HasHooks
{
    public function registerHooks(HookManager $hooks): void
    {
        $hooks->addAction('storefront.layout.before_header', function (): void {
            echo view('announcement-bar::bar')->render();
        });

        $hooks->addFilter('cart.discount_total', function (float $total): float {
            return $total;
        }, priority: 20);
    }
}
```

Host applications can call hooks with:

```php
do_action('storefront.layout.before_header');

$price = apply_filters('product.final_price', $price, $product);
```

Hook naming convention:

```text
domain.area.event
```

Examples:

- `storefront.layout.before_header`
- `checkout.order.before_create`
- `checkout.order.after_create`
- `cart.line.before_total`
- `product.price.after_resolve`
- `admin.sidebar.items`

Actions are for side effects. Filters must return the transformed value.

## Admin UI Integration

Implement `HasAdminPanel` when an add-on contributes admin pages.

```php
use Illuminate\Support\Facades\Route;
use Richness\RichAddons\Contracts\HasAdminPanel;

final class AnnouncementBarAddon extends AbstractAddon implements HasAdminPanel
{
    public function registerAdminRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->prefix('admin/addons/announcement-bar')
            ->name('admin.addons.announcement-bar.')
            ->group(__DIR__ . '/../routes/admin.php');
    }

    public function adminNavItems(): array
    {
        return [
            [
                'label' => 'Announcement Bar',
                'route' => 'admin.addons.announcement-bar.index',
                'icon' => 'fa-solid fa-bullhorn',
                'active_pattern' => 'admin/addons/announcement-bar*',
            ],
        ];
    }
}
```

Admin pages should follow the host application's design system and middleware.
Never expose add-on admin routes without authentication and authorization.

## Storefront Widgets

Implement `HasStorefrontWidgets` when the add-on renders storefront fragments.

```php
use Richness\RichAddons\Contracts\HasStorefrontWidgets;

final class AnnouncementBarAddon extends AbstractAddon implements HasStorefrontWidgets
{
    public function widgets(): array
    {
        return [
            'storefront.header.before' => fn () => view('announcement-bar::bar')->render(),
        ];
    }
}
```

Widgets should return HTML strings, renderable views, or callables accepted by
the host application. Keep widget rendering fast and cache data when needed.

## Frontend Integration

An add-on may ship Blade views, CSS, JavaScript, Livewire components, or assets.

Recommended layout:

```text
resources/
  views/
    admin/
      index.blade.php
    storefront/
      bar.blade.php
  js/
    admin.js
  css/
    storefront.css
```

Guidelines:

- Scope CSS classes with an add-on prefix, for example `.ra-announcement-bar`.
- Avoid global CSS resets.
- Avoid modifying host Vite config from inside the add-on.
- Prefer Blade components or view namespaces over editing host templates.
- Use hooks and widgets for storefront placement.
- Keep JavaScript progressive: the feature should fail gracefully when JS is not loaded.
- Do not place secrets, license keys, system keys, or payment data in frontend code.

## Marketplace Flow

The consuming app talks to the central marketplace using:

- `GET /api/v1/addons/catalog`
- `GET` release download URL from the catalog metadata
- `POST /api/v1/addons/licenses/activate`
- `POST /api/v1/addons/licenses/ping`

Requests include:

- `X-Rich-Addons-System: <system_key>`
- `Authorization: Bearer <system_secret>` when configured
- The current domain from the consuming application

The central system is responsible for:

- Maintaining catalog records.
- Publishing release metadata.
- Signing release checksums.
- Issuing license keys.
- Signing license payloads.
- Enforcing paid/subscription download eligibility.
- Granting manual access for selected systems/domains.
- Applying subscription expiration and grace periods.

## Signed Release Requirements

Marketplace add-ons are installed from ZIP releases. A release is accepted only
when:

- The ZIP downloads successfully.
- The SHA-256 checksum matches catalog metadata.
- The signature validates.
- The archive contains `addon.json`.
- The manifest `id` matches the selected add-on.
- The manifest `version` matches the selected release.
- The archive does not contain unsafe paths such as `../file.php` or absolute paths.

The installer extracts to a staging directory first, validates the release, then
moves it to `RICH_ADDONS_PATH`.

## Licensing Flow

Paid and subscription add-ons require a valid license.

Activation:

1. Admin installs the add-on from marketplace.
2. Admin enters a license key in the consuming application.
3. The package sends license key, add-on id, system key, and domain to the central system.
4. The central system returns a signed payload.
5. The package verifies the signature and stores the payload locally.

Runtime:

1. On boot, free add-ons load normally.
2. Paid/subscription add-ons are checked before autoload and boot.
3. A valid signed payload allows the add-on to boot.
4. An expired license may continue only until `grace_until`.
5. After `grace_until`, the add-on is marked `suspended` and is not booted.

The runtime never deletes customer files or data as a licensing action. It
disables execution by refusing to boot unlicensed paid/subscription add-ons.

## License Payload Shape

The central system should sign a payload shaped like:

```json
{
  "license_key": "RICH-XXXX-XXXX-XXXX",
  "addon_id": "richness/announcement-bar",
  "domain": "client.test",
  "allowed_domains": ["client.test", "staging.client.test"],
  "system_key": "client-store-001",
  "status": "active",
  "expires_at": "2026-10-07T00:00:00+00:00",
  "grace_until": "2026-10-22T00:00:00+00:00",
  "timestamp": "2026-09-07T00:00:00+00:00",
  "signature_alg": "openssl-sha256",
  "signature": "base64-signature"
}
```

The signature is calculated over the same payload without the `signature` key.

Supported algorithms:

- `openssl-sha256`: recommended for production.
- `hmac-sha256`: useful for local development and controlled internal systems.

## Security Rules

- Do not add backdoors.
- Do not delete files or customer data because of billing state.
- Do not execute arbitrary uploaded PHP.
- Do not install a ZIP unless checksum and signature pass.
- Do not trust marketplace responses without signature verification.
- Do not store private signing keys in consuming applications.
- Do not bind a paid license to only a license key; bind it to add-on id, domain,
  system key, status, expiration, and signature.
- Do not use loose domain checks such as `str_contains`.
- Do not allow `../` or absolute paths in release archives.
- Do not expose admin routes without host application middleware.

## Building a New Add-on

1. Choose a stable id, for example `richness/reviews`.
2. Create an add-on folder with `addon.json`.
3. Create a main class extending `AbstractAddon`.
4. Add `psr4` mapping in the manifest for local discovery.
5. Implement optional interfaces:
   - `HasHooks` for backend/storefront integration.
   - `HasAdminPanel` for admin routes and nav items.
   - `HasStorefrontWidgets` for renderable storefront blocks.
6. Add migrations for add-on-owned data.
7. Add Blade/Livewire/assets under `resources`.
8. Test install, activate, boot, deactivate, and uninstall paths.
9. Package a ZIP containing `addon.json` at the archive root.
10. Publish release metadata from the central marketplace.

## Example Minimal Add-on

```text
addons/richness-reviews/
  addon.json
  src/ReviewsAddon.php
```

`addon.json`:

```json
{
  "id": "richness/reviews",
  "name": "Reviews",
  "version": "1.0.0",
  "author": "Richness Agency",
  "description": "Adds product reviews.",
  "tier": "free",
  "provider": "Richness\\Reviews\\ReviewsAddon",
  "psr4": {
    "Richness\\Reviews\\": "src/"
  }
}
```

`src/ReviewsAddon.php`:

```php
<?php

declare(strict_types=1);

namespace Richness\Reviews;

use Richness\RichAddons\Support\AbstractAddon;

final class ReviewsAddon extends AbstractAddon
{
    public function id(): string
    {
        return 'richness/reviews';
    }

    public function name(): string
    {
        return 'Reviews';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function boot(): void
    {
        //
    }
}
```

## Testing Checklist

For every add-on:

- Manifest parses successfully.
- Discovery creates or updates the `rich_addons` record.
- Free add-ons activate without a license.
- Paid/subscription add-ons reject empty or invalid license keys.
- Signed license payload works only for the intended add-on, domain, and system.
- Expired license inside grace period still boots.
- Expired license after grace period becomes `suspended` and does not boot.
- Marketplace ZIP rejects checksum mismatch.
- Marketplace ZIP rejects unsafe paths.
- Admin routes require authentication.
- Frontend widgets render without leaking secrets.

## Composer And Packagist

The package is distributed as:

```bash
composer require richnessagency/rich-addons
```

Use Packagist auto-update or a GitHub webhook so every pushed commit/tag becomes
available to Composer. For stable production releases, prefer semantic version
tags:

```bash
git tag v1.0.0
git push origin v1.0.0
```

Host applications may require `^1.0` for stable releases or `dev-main` for
active internal development.

## License

MIT License. Created by Richness Agency.
