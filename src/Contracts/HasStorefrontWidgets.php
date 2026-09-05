<?php

declare(strict_types=1);

namespace Richness\RichAddons\Contracts;

/**
 * Implement this on an Addon to contribute storefront widgets.
 */
interface HasStorefrontWidgets
{
    /**
     * Return widget render callables keyed by widget slot name.
     *
     * @return array<string, callable>
     */
    public function widgets(): array;
}
