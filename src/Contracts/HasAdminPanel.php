<?php

declare(strict_types=1);

namespace Richness\RichAddons\Contracts;

/**
 * Implement this on an Addon to declare that it contributes admin panel pages.
 */
interface HasAdminPanel
{
    /**
     * Return the admin route definitions for this add-on.
     * Called during boot — the add-on should register its admin routes here.
     */
    public function registerAdminRoutes(): void;

    /**
     * Return sidebar navigation items contributed by this add-on.
     *
     * @return list<array{label: string, route: string, icon: string, active_pattern?: string}>
     */
    public function adminNavItems(): array;
}
