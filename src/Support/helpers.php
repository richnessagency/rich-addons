<?php

declare(strict_types=1);

use Richness\RichAddons\Hooks\HookManager;
use Richness\RichAddons\Kernel\AddonKernel;

if (! function_exists('rich_addon_hooks')) {
    function rich_addon_hooks(): HookManager
    {
        return app(HookManager::class);
    }
}

if (! function_exists('do_action')) {
    function do_action(string $hook, mixed ...$args): void
    {
        rich_addon_hooks()->doAction($hook, ...$args);
    }
}

if (! function_exists('apply_filters')) {
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
    {
        return rich_addon_hooks()->applyFilters($hook, $value, ...$args);
    }
}

if (! function_exists('rich_addon_kernel')) {
    function rich_addon_kernel(): AddonKernel
    {
        return app(AddonKernel::class);
    }
}
