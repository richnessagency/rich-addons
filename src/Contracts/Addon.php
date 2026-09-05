<?php

declare(strict_types=1);

namespace Richness\RichAddons\Contracts;

use Richness\RichAddons\Data\AddonTier;

/**
 * Every add-on MUST implement this interface — either directly or by
 * extending the base {@see \Richness\RichAddons\Support\AddonServiceProvider}.
 */
interface Addon
{
    /** Unique machine identifier, e.g. "rich-addon-reviews". */
    public function identifier(): string;

    /** Human-readable display name. */
    public function name(): string;

    /** Semantic version string. */
    public function version(): string;

    /** Author name or organization. */
    public function author(): string;

    /** Brief description of the add-on's purpose. */
    public function description(): string;

    /** Minimum host application version required (nullable = any). */
    public function minimumAppVersion(): ?string;

    /** Pricing tier: free, paid, or subscription. */
    public function tier(): AddonTier;

    /** Called once when the add-on is first installed (run migrations, seed data). */
    public function install(): void;

    /** Called every time the application boots while the add-on is active. */
    public function boot(): void;

    /** Called when the add-on is uninstalled (optional cleanup). */
    public function uninstall(): void;
}
