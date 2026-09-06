# Rich Addons Agent Guide

This file is for AI coding agents, MCP tools, and engineers working on
`richnessagency/rich-addons`.

## Project Model

`rich-addons` is a reusable Laravel package. It is the client-side runtime for
add-ons used by consuming applications such as commerce platforms. The central
marketplace/core application is a separate Laravel app that owns catalog,
payments, subscriptions, signed releases, and license issuance.

Keep this package generic. Do not add host-application-specific business logic
unless it is expressed as a reusable extension point.

## Runtime Boundaries

The package may:

- Discover local add-ons from `RICH_ADDONS_PATH`.
- Store add-on records in `rich_addons`.
- Sync catalog metadata from the central marketplace.
- Download and install signed release ZIPs.
- Verify release checksums and signatures.
- Activate and validate paid/subscription licenses.
- Prevent unlicensed paid/subscription add-ons from booting.
- Register hooks, filters, admin routes, and widgets.

The package must not:

- Delete customer files or data because of payment or license state.
- Add hidden access, backdoors, web shells, or remote command execution.
- Execute arbitrary uploaded PHP.
- Store marketplace private keys in consuming applications.
- Trust unsigned release or license metadata.
- Edit host application source files during normal add-on installation.
- Bypass host authentication or authorization middleware.

License enforcement is non-destructive. If an add-on is unpaid after its grace
period, mark it `suspended`, clear the active add-on cache, and skip boot.

## Important Source Files

- `src/Kernel/AddonKernel.php`: discovery, activation toggling, boot flow, and
  runtime license gate.
- `src/Licensing/CryptographicLicenseVerifier.php`: signed license payload
  verification and central ping/activation calls.
- `src/Release/SignedReleaseInstaller.php`: release download, checksum check,
  archive path validation, staging, and manifest validation.
- `src/Release/HashSignatureVerifier.php`: HMAC/OpenSSL release signature check.
- `src/Marketplace/CentralMarketplaceClient.php`: central API client.
- `src/Marketplace/RefreshMarketplaceCatalog.php`: catalog sync into local DB.
- `src/Marketplace/InstallMarketplaceAddon.php`: install selected marketplace
  release.
- `src/Data/AddonManifest.php`: manifest parser and compatibility aliases.
- `src/Hooks/HookManager.php`: actions and filters.
- `src/Contracts/*`: extension interfaces used by add-ons.
- `config/rich-addons.php`: all package configuration.
- `database/migrations/*`: local runtime schema.

## Coding Rules

- Target PHP 8.3+ and Laravel/Illuminate 13.
- Use strict types in PHP files.
- Prefer small, explicit classes over framework magic.
- Keep public contracts backward compatible when possible.
- Preserve support for both `provider` and legacy `mainClass` manifest keys.
- Preserve support for `id` and legacy `identifier` manifest keys.
- Use Laravel facades and helpers consistently with the existing codebase.
- Never introduce network calls in hot paths unless they are cached or required
  for licensing. The license verifier already prefers signed local payloads.
- Keep filesystem writes inside configured add-on, staging, download, or package
  publish paths.
- Validate all archive entries before extraction.
- Use `hash_equals` for secret/signature comparisons.
- Normalize domains before matching.
- Exact domain and subdomain matches are allowed. Loose substring matching is not.

## Documentation Rules

When adding or changing behavior, update `README.md` if it affects:

- Installation.
- Configuration.
- Manifest schema.
- Add-on authoring.
- Marketplace publishing.
- Licensing behavior.
- Security expectations.
- Required central API payloads.

## Test Expectations

Add or update tests in the consuming app or package test suite for:

- Manifest parsing changes.
- Activation/deactivation behavior.
- Paid/subscription license validation.
- Expired license and grace-period behavior.
- Marketplace catalog response parsing.
- Release checksum/signature failures.
- Unsafe ZIP paths.
- Domain/system binding.

Minimum manual verification after package changes:

```bash
composer validate --no-check-publish
php -l src/Kernel/AddonKernel.php
```

When testing from a consuming Laravel app:

```bash
composer update richnessagency/rich-addons --with-dependencies
php artisan test tests/Feature/RichAddons tests/Unit/RichAddons
```

## Marketplace/Core Contract

The central marketplace should expose:

- `GET /api/v1/addons/catalog`
- Signed release download URLs.
- `POST /api/v1/addons/licenses/activate`
- `POST /api/v1/addons/licenses/ping`

Client requests include:

- `X-Rich-Addons-System`
- Optional bearer token from `RICH_ADDONS_SYSTEM_SECRET`
- Current consuming domain.

License payloads must include:

- `license_key`
- `addon_id`
- `domain`
- `allowed_domains`
- `system_key`
- `status`
- `expires_at`
- `grace_until`
- `timestamp`
- `signature_alg`
- `signature`

The central application signs the payload without `signature`. The consuming app
verifies the signature with the configured public key or HMAC development secret.

## Release Contract

Each release ZIP must contain `addon.json` at archive root. Catalog release
metadata must include:

- `addon_id`
- `version`
- `download_url`
- `checksum`
- `signature`
- optional `composer_package`

The package rejects releases with mismatched checksum, invalid signature,
missing manifest, mismatched add-on id/version, or unsafe archive paths.

## Safe Licensing Policy

Billing state can control whether code is allowed to run. It must never be used
to corrupt, erase, encrypt, or hold customer files hostage.

Allowed:

- Block download of paid releases without entitlement.
- Reject activation without a valid license.
- Skip boot when license is invalid.
- Mark the local add-on record as `suspended`.
- Show admin-facing failure reasons.

Forbidden:

- Deleting add-on files after non-payment.
- Deleting customer data.
- Adding hidden remote access.
- Obfuscating malicious behavior as license enforcement.
- Breaking the host application outside the specific unpaid add-on.

## Composer Release Workflow

This package is distributed through Composer/Packagist from GitHub. After changes:

```bash
git status --short
composer validate --no-check-publish
git add .
git commit -m "Describe the package change"
git push origin main
```

For production-ready releases:

```bash
git tag vX.Y.Z
git push origin vX.Y.Z
```

Consuming apps should update through Composer:

```bash
composer update richnessagency/rich-addons --with-dependencies
```
