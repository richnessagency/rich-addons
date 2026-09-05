<?php

declare(strict_types=1);

namespace Richness\RichAddons\Data;

final readonly class LicenseResult
{
    public function __construct(
        public bool $valid,
        public string $message = '',
        public ?string $expiresAt = null,
    ) {}

    public static function ok(string $message = 'License valid', ?string $expiresAt = null): self
    {
        return new self(valid: true, message: $message, expiresAt: $expiresAt);
    }

    public static function fail(string $message): self
    {
        return new self(valid: false, message: $message);
    }

    /** Convenience: free add-ons always pass. */
    public static function free(): self
    {
        return new self(valid: true, message: 'Free add-on — no license required.');
    }
}
