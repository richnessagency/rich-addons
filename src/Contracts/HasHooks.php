<?php

declare(strict_types=1);

namespace Richness\RichAddons\Contracts;

use Richness\RichAddons\Hooks\HookManager;

/**
 * Implement this on an Addon to register hooks (actions & filters).
 */
interface HasHooks
{
    /**
     * Register all action and filter listeners with the HookManager.
     */
    public function registerHooks(HookManager $hooks): void;
}
