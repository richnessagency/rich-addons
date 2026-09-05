<?php

declare(strict_types=1);

namespace Richness\RichAddons\Contracts;

use Richness\RichAddons\Data\AddonManifest;
use Richness\RichAddons\Data\LicenseResult;
use Richness\RichAddons\Models\AddonModel;

interface LicenseVerifier
{
    /**
     * Verify the license for a given add-on manifest & model record.
     */
    public function verify(string $licenseKey, AddonManifest $manifest, AddonModel $record): LicenseResult;
}
