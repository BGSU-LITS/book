<?php

declare(strict_types=1);

namespace Lits\Action;

use League\Period\Period;
use Lits\Config\BookConfig;
use Lits\LibCal\Data\Space\AvailabilitySpaceData;
use Lits\LibCal\Data\Space\CategorySpaceData;
use Lits\LibCal\Data\Space\ItemSpaceData;
use Lits\LibCal\Exception\NotFoundException;
use Lits\Meta\CategoryMeta;
use Lits\Meta\ItemMeta;
use Lits\Meta\LocationMeta;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;

trait TraitBookItem
{
    use TraitBookLocation;

    /**
     * @throws HttpBadRequestException
     * @throws HttpNotFoundException
     */
    private function findItem(LocationMeta $location): ItemMeta
    {
        if (!isset($this->data['item'])) {
            throw new HttpBadRequestException($this->request);
        }

        foreach ($location->items as $item) {
            if ($item->slug === $this->data['item']) {
                return $item;
            }
        }

        throw new HttpNotFoundException($this->request);
    }

    /**
     * @throws HttpBadRequestException
     * @throws HttpInternalServerErrorException
     * @throws HttpNotFoundException
     */
    private function findCategory(ItemMeta $item): CategoryMeta
    {
        if (!($this->settings['book'] instanceof BookConfig)) {
            throw new HttpInternalServerErrorException($this->request);
        }

        if (\is_int($item->data->groupId)) {
            $result = $this->findCategorySpaceData($item->data->groupId);
            $category = \reset($result);

            if ($category !== false) {
                return new CategoryMeta(
                    $category,
                    $this->settings['book']->categories,
                );
            }
        }

        throw new HttpNotFoundException($this->request);
    }

    /**
     * @return array<CategorySpaceData>
     * @throws HttpInternalServerErrorException
     * @throws HttpNotFoundException
     */
    private function findCategorySpaceData(int $groupId): array
    {
        try {
            return $this->client->space()
                ->category($groupId)
                ->cache()
                ->send();
        } catch (NotFoundException $exception) {
            throw new HttpNotFoundException(
                $this->request,
                null,
                $exception,
            );
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception,
            );
        }
    }

    /**
     * @return array<AvailabilitySpaceData>
     * @throws HttpInternalServerErrorException
     * @throws HttpNotFoundException
     */
    private function getAvailability(
        int $item_id,
        ?string $from,
        ?string $to,
        bool $cache = false,
    ): array {
        if (\is_null($from) || \is_null($to)) {
            return [];
        }

        $result = $this->getAvailabilityItemSpaceData(
            $item_id,
            $from,
            $to,
            $cache,
        );

        $item = \reset($result);

        if ($item instanceof ItemSpaceData && !\is_null($item->availability)) {
            return $item->availability;
        }

        return [];
    }

    /**
     * @return array<ItemSpaceData>
     * @throws HttpInternalServerErrorException
     * @throws HttpNotFoundException
     */
    private function getAvailabilityItemSpaceData(
        int $item_id,
        string $from,
        string $to,
        bool $cache,
    ): array {
        try {
            $client = $this->client->space()
                ->item($item_id)
                ->setAvailability([$from, $to]);

            if ($cache) {
                $client->cache();
            }

            return $client->send();
        } catch (NotFoundException $exception) {
            throw new HttpNotFoundException(
                $this->request,
                null,
                $exception,
            );
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception,
            );
        }
    }

    /**
     * @param array<AvailabilitySpaceData> $availability
     * @throws HttpInternalServerErrorException
     */
    private function setDivisor(ItemMeta &$item, array $availability): void
    {
        $available = \reset($availability);

        if ($available === false) {
            return;
        }

        try {
            $period = Period::fromDate($available->from, $available->to);
            $item->lengthDivisor = $period->dateInterval();
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception,
            );
        }
    }
}
