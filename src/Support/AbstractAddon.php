<?php

declare(strict_types=1);

namespace Richness\RichAddons\Support;

use Richness\RichAddons\Contracts\Addon;
use Richness\RichAddons\Data\AddonTier;

abstract class AbstractAddon implements Addon
{
    public function identifier(): string
    {
        return $this->id();
    }

    public function id(): string
    {
        return 'unknown';
    }

    public function author(): string
    {
        return 'Richness Agency';
    }

    public function description(): string
    {
        return '';
    }

    public function minimumAppVersion(): ?string
    {
        return '1.0.0';
    }

    public function tier(): AddonTier
    {
        return AddonTier::Free;
    }

    public function install(): void {}

    public function uninstall(): void {}
}
