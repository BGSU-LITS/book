<?php

declare(strict_types=1);

namespace Lits\Action;

use Lits\Config\BookConfig;
use Lits\LibCal\Client;
use Lits\LibCal\Data\Space\AvailabilitySpaceData;
use Lits\LibCal\Data\Space\CategorySpaceData;
use Lits\LibCal\Data\Space\ItemSpaceData;
use Lits\LibCal\Data\Space\LocationSpaceData;
use Lits\LibCal\Data\Space\ZoneSpaceData;
use Lits\LibCal\Exception\ClientException;
use Lits\LibCal\Exception\NotFoundException;
use Lits\Meta\LocationMeta;
use Lits\Service\ActionService;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;

trait TraitBookLocation
{
    private Client $client;

    public function __construct(ActionService $service, Client $client)
    {
        parent::__construct($service);

        $this->client = $client;
    }

    /**
     * @throws HttpBadRequestException
     * @throws HttpInternalServerErrorException
     * @throws HttpNotFoundException
     */
    private function findLocation(): LocationMeta
    {
        try {
            $locations = $this->client->space()
                ->locations()
                ->setDetails()
                ->cache()
                ->send();
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception
            );
        }

        return $this->findLocationMeta($locations);
    }

    /**
     * @param LocationSpaceData[] $locations
     * @throws HttpBadRequestException
     * @throws HttpInternalServerErrorException
     * @throws HttpNotFoundException
     */
    private function findLocationMeta(array $locations): LocationMeta
    {
        if (!($this->settings['book'] instanceof BookConfig)) {
            throw new HttpInternalServerErrorException($this->request);
        }

        if (!isset($this->data['location'])) {
            throw new HttpBadRequestException($this->request);
        }

        foreach ($locations as $location) {
            $meta = new LocationMeta(
                $location,
                $this->settings['book']->locations
            );

            if ($meta->slug === $this->data['location']) {
                return $meta;
            }
        }

        throw new HttpNotFoundException($this->request);
    }

    /**
     * @param mixed[] $query
     * @return ItemSpaceData[]
     * @throws HttpInternalServerErrorException
     * @throws HttpNotFoundException
     */
    private function getItems(int $location_id, array $query = []): array
    {
        try {
            $items = $this->client->space()
                ->items($location_id)
                ->setAvailability(self::queryDate($query))
                ->setAccessibleOnly(isset($query['accessible']))
                ->setPowered(isset($query['powered']))
                ->setBookable(!isset($query['seats']))
                ->cache()
                ->send();
        } catch (ClientException $exception) {
            return [];
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

        return self::filterItems($items, $query);
    }

    /**
     * @return CategorySpaceData[]
     * @throws HttpInternalServerErrorException
     * @throws HttpNotFoundException
     */
    private function getCategories(int $location_id): array
    {
        try {
            $result = $this->client->space()
                ->categories($location_id)
                ->cache()
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

        $categories = \reset($result);

        if ($categories === false) {
            return [];
        }

        return $categories->categories;
    }

    /**
     * @return ZoneSpaceData[]
     * @throws HttpInternalServerErrorException
     * @throws HttpNotFoundException
     */
    private function getZones(int $location_id): array
    {
        try {
            return $this->client->space()
                ->zones($location_id)
                ->cache()
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
    }

    /** @param mixed[] $query */
    private static function queryDate(array $query): string
    {
        if (
            isset($query['date']) &&
            \is_string($query['date']) &&
            $query['date'] !== ''
        ) {
            return \str_replace(' to ', ',', $query['date']);
        }

        return 'next';
    }

    /** @param mixed[] $query */
    private static function queryTime(array $query): string
    {
        if (
            isset($query['time']) &&
            \is_string($query['time']) &&
            $query['time'] !== ''
        ) {
            return $query['time'];
        }

        return '';
    }

    /**
     * @param ItemSpaceData[] $items
     * @param mixed[] $query
     * @return ItemSpaceData[]
     */
    private static function filterItems(array $items, array $query): array
    {
        $items = \array_filter(
            $items,
            fn ($item) =>
                !isset($query['capacity']) ||
                $item->capacity > (int) $query['capacity']
        );

        $result = [];

        foreach ($items as $item) {
            $item->availability = self::filterAvailability(
                $item->availability,
                self::queryTime($query)
            );

            if ($item->availability !== []) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * @param AvailabilitySpaceData[]|null $availability
     * @return AvailabilitySpaceData[]
     */
    private static function filterAvailability(
        ?array $availability,
        string $time
    ): array {
        if (\is_null($availability) || $time === '') {
            return (array) $availability;
        }

        return \array_filter(
            $availability,
            fn ($available) =>
                $time >= $available->from->format('H:i') &&
                $time < $available->to->format('H:i')
        );
    }
}
