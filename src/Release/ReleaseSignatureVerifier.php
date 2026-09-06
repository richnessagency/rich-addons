<?php

declare(strict_types=1);

namespace Richness\RichAddons\Release;

interface ReleaseSignatureVerifier
{
    public function verifyFile(string $path, string $expectedChecksum, string $signature): bool;
}
