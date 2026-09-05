<?php

declare(strict_types=1);

namespace Richness\RichAddons\Data;

enum AddonTier: string
{
    case Free = 'free';
    case Paid = 'paid';
    case Subscription = 'subscription';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'مجاني',
            self::Paid => 'مدفوع',
            self::Subscription => 'اشتراك',
        };
    }

    public function requiresLicense(): bool
    {
        return $this !== self::Free;
    }
}
