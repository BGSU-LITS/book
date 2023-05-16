<?php

declare(strict_types=1);

namespace Lits\Meta;

use Lits\Config\MetaConfig;
use Lits\LibCal\Data\Space\CategorySpaceData;
use Lits\LibCal\Data\Space\ItemSpaceData;
use Lits\LibCal\Data\Space\LocationSpaceData;
use Lits\LibCal\Data\Space\ZoneSpaceData;
use Lits\Meta;

final class LocationMeta extends Meta
{
    public LocationSpaceData $data;
    public ?int $capacity = null;
    public ?\DateTimeInterface $availability = null;

    /** @var ItemMeta[] $items */
    public array $items = [];

    /** @var CategoryMeta[] */
    public array $categories = [];

    /** @var ZoneMeta[] */
    public array $zones = [];

    /** @param MetaConfig[] $configs */
    public function __construct(LocationSpaceData $data, array $configs)
    {
        $this->id = $data->lid;
        $this->data = $data;
        $this->setSlug($data->name);
        $this->setConfig($configs);
    }

    /**
     * @param ItemSpaceData[] $items
     * @param MetaConfig[] $configs
     */
    public function loadItems(array $items, array $configs): void
    {
        $this->items = [];

        foreach ($items as $item) {
            if ($item->capacity > $this->capacity) {
                $this->capacity = $item->capacity;
            }

            $this->loadAvailability($item);

            $this->items[] = new ItemMeta($item, $configs);
        }
    }

    /**
     * @param CategorySpaceData[] $categories
     * @param MetaConfig[] $configs
     */
    public function loadCategories(array $categories, array $configs): void
    {
        foreach ($categories as $category) {
            $this->loadCategoriesMatches($category, $configs);
        }
    }

    /** @param ZoneSpaceData[] $zones */
    public function loadZones(array $zones): void
    {
        $this->zones = [];

        foreach ($zones as $zone) {
            $this->loadZonesMatches($zone);
        }
    }

    private function loadAvailability(ItemSpaceData $item): void
    {
        if (!\is_array($item->availability)) {
            return;
        }

        foreach ($item->availability as $availability) {
            if (
                \is_null($this->availability) ||
                $availability->from < $this->availability
            ) {
                $this->availability = $availability->from;
            }
        }
    }

    /** @param MetaConfig[] $configs */
    private function loadCategoriesMatches(
        CategorySpaceData $category,
        array $configs
    ): void {
        foreach ($this->items as $item) {
            if ($item->data->groupId === $category->cid) {
                $this->categories[] = new CategoryMeta(
                    $category,
                    $configs
                );

                break;
            }
        }
    }

    private function loadZonesMatches(ZoneSpaceData $zone): void
    {
        foreach ($this->items as $item) {
            if ($item->data->zoneId === $zone->id) {
                $this->zones[] = new ZoneMeta($zone);

                break;
            }
        }
    }
}
