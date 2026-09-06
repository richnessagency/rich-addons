<?php

declare(strict_types=1);

namespace Richness\RichAddons\Models;

use Illuminate\Database\Eloquent\Model;
use Richness\RichAddons\Data\AddonStatus;
use Richness\RichAddons\Data\AddonTier;

/**
 * @property int $id
 * @property string $addon_id
 * @property string $name
 * @property string|null $description
 * @property string $version
 * @property string|null $author
 * @property string|null $repository
 * @property AddonTier $tier
 * @property AddonStatus $status
 * @property string|null $license_key
 * @property array|null $manifest
 * @property string|null $installed_path
 * @property string|null $staged_path
 * @property string|null $source
 * @property string|null $release_checksum
 * @property string|null $release_signature
 * @property string|null $license_payload
 * @property string|null $failure_reason
 * @property \Illuminate\Support\Carbon|null $activated_at
 * @property \Illuminate\Support\Carbon|null $installed_at
 * @property \Illuminate\Support\Carbon|null $last_marketplace_sync_at
 */
class AddonModel extends Model
{
    protected $table = 'rich_addons';

    protected $fillable = [
        'addon_id',
        'name',
        'description',
        'version',
        'author',
        'repository',
        'tier',
        'status',
        'license_key',
        'manifest',
        'installed_path',
        'staged_path',
        'source',
        'release_checksum',
        'release_signature',
        'license_payload',
        'last_license_result',
        'failure_reason',
        'installed_at',
        'last_marketplace_sync_at',
        'activated_at',
    ];

    protected $casts = [
        'tier' => AddonTier::class,
        'status' => AddonStatus::class,
        'manifest' => 'array',
        'license_payload' => 'array',
        'last_license_result' => 'array',
        'installed_at' => 'datetime',
        'last_marketplace_sync_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    public function isActive(): bool
    {
        return $this->status === AddonStatus::Active;
    }

    public function activate(): bool
    {
        $this->status = AddonStatus::Active;
        $this->activated_at = now();

        return $this->save();
    }

    public function deactivate(): bool
    {
        $this->status = AddonStatus::Inactive;

        return $this->save();
    }
}
