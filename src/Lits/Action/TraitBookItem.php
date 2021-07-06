<?php

declare(strict_types=1);

namespace Lits\Action;

use League\Period\Duration;
use League\Period\Period;
use Lits\Config\BookConfig;
use Lits\LibCal\Data\Space\AvailabilitySpaceData;
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
            try {
                $result = $this->client->space()
                    ->category($item->data->groupId)
                    ->send();
            } catch (NotFoundException $exception) {
                throw new HttpNotFoundException(
                    $this->request,
                    null,
                    $exception
                );
            } catch (\Throwable $exception) {
                throw new HttpInternalServerErrorException(
                    $this->request,
                    null,
                    $exception
                );
            }

            $category = \reset($result);

            if ($category !== false) {
                return new CategoryMeta(
                    $category,
                    $this->settings['book']->categories
                );
            }
        }

        throw new HttpNotFoundException($this->request);
    }

    /**
     * @return AvailabilitySpaceData[]
     * @throws HttpInternalServerErrorException
     * @throws HttpNotFoundException
     */
    private function getAvailability(
        int $item_id,
        ?string $from,
        ?string $to
    ): array {
        if (\is_null($from) || \is_null($to)) {
            return [];
        }

        try {
            $result = $this->client->space()
                ->item($item_id)
                ->setAvailability([$from, $to])
                ->send();
        } catch (NotFoundException $exception) {
            throw new HttpNotFoundException(
                $this->request,
                null,
                $exception
            );
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception
            );
        }

        $item = \reset($result);

        if ($item instanceof ItemSpaceData && !\is_null($item->availability)) {
            return $item->availability;
        }

        return [];
    }

    /**
     * @param AvailabilitySpaceData[] $availability
     * @throws HttpInternalServerErrorException
     */
    private function setDivisor(ItemMeta &$item, array $availability): void
    {
        $available = \reset($availability);

        if ($available === false) {
            return;
        }

        try {
            $item->lengthDivisor = Duration::create(
                new Period($available->from, $available->to)
            );
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception
            );
        }
    }
}
