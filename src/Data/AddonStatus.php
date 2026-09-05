<?php

declare(strict_types=1);

namespace Richness\RichAddons\Data;

enum AddonStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'نشط',
            self::Inactive => 'معطل',
            self::Suspended => 'معلّق',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Active => '🟢',
            self::Inactive => '⚪',
            self::Suspended => '🔴',
        };
    }
}
