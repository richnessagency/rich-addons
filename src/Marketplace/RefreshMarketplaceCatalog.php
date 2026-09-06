<?php

declare(strict_types=1);

namespace Richness\RichAddons\Marketplace;

use Illuminate\Support\Collection;
use Richness\RichAddons\Data\MarketplaceAddonData;
use Richness\RichAddons\Models\AddonModel;

class RefreshMarketplaceCatalog
{
    public function __construct(
        protected CentralMarketplaceClient $client,
    ) {}

    /**
     * @return Collection<int, AddonModel>
     */
    public function handle(): Collection
    {
        $payload = $this->client->catalog();
        $items = (array) ($payload['addons'] ?? $payload['data'] ?? []);
        $synced = collect();

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $addon = MarketplaceAddonData::fromArray($item);

            if ($addon->id === '') {
                continue;
            }

            $release = $addon->release;

            $synced->push(AddonModel::updateOrCreate(
                ['addon_id' => $addon->id],
                [
                    'name' => $addon->name,
                    'description' => $addon->description,
                    'version' => $addon->version,
                    'author' => $addon->author,
                    'tier' => $addon->tier,
                    'source' => 'marketplace',
                    'manifest' => $item,
                    'release_checksum' => $release['checksum'] ?? null,
                    'release_signature' => $release['signature'] ?? null,
                    'last_marketplace_sync_at' => now(),
                    'failure_reason' => null,
                ]
            ));
        }

        return $synced;
    }
}
