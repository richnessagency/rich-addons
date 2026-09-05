<?php

declare(strict_types=1);

namespace Richness\RichAddons\Contracts;

use Richness\RichAddons\Data\AddonManifest;
use Richness\RichAddons\Data\AddonTier;
use Richness\RichAddons\Data\LicenseResult;
use Richness\RichAddons\Models\AddonLicense;

/**
 * Strategy interface for license verification.
 *
 * Different implementations handle free (NullVerifier), paid (SignatureVerifier),
 * and subscription (HeartbeatVerifier) add-ons.
 */
interface LicenseVerifier
{
    /** Check whether this verifier handles the given tier. */
    public function supports(AddonTier $tier): bool;

    /** Verify the license for a given add-on manifest. */
    public function verify(AddonManifest $manifest, ?AddonLicense $license): LicenseResult;
}
