<?php

declare(strict_types=1);

namespace Lits\Action;

use Lits\Config\BookConfig;
use Lits\LibCal\Client;
use Lits\LibCal\Data\Space\CategorySpaceData;
use Lits\LibCal\Data\Space\ItemSpaceData;
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
        if (!($this->settings['book'] instanceof BookConfig)) {
            throw new HttpInternalServerErrorException($this->request);
        }

        if (!isset($this->data['location'])) {
            throw new HttpBadRequestException($this->request);
        }

        try {
            $locations = $this->client->space()
                ->locations()
                ->setDetails()
                ->send();
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception
            );
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
        $date = 'next';

        if (
            isset($query['date']) &&
            \is_string($query['date']) &&
            $query['date'] !== ''
        ) {
            $date = \str_replace(' to ', ',', $query['date']);
        }

        try {
            $items = $this->client->space()
                ->items($location_id)
                ->setAvailability($date)
                ->setAccessibleOnly(isset($query['accessible']))
                ->setPowered(isset($query['powered']))
                ->setBookable(!isset($query['seats']))
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

        if ($query !== []) {
            $items = self::filterItems($items, $query);
        }

        return $items;
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

    /**
     * @param ItemSpaceData[] $items
     * @param mixed[] $query
     * @return ItemSpaceData[]
     */
    private static function filterItems(array $items, array $query): array
    {
        $result = [];

        foreach ($items as $item) {
            if (
                isset($query['capacity']) &&
                $item->capacity < (int) $query['capacity']
            ) {
                continue;
            }

            if (
                isset($query['date']) &&
                \is_string($query['date']) &&
                $query['date'] !== ''
            ) {
                if (\is_null($item->availability)) {
                    continue;
                }

                if (
                    isset($query['time']) &&
                    \is_string($query['time']) &&
                    $query['time'] !== ''
                ) {
                    foreach ($item->availability as $key => $available) {
                        if (
                            $query['time'] < $available->from->format('H:i') ||
                            $query['time'] >= $available->to->format('H:i')
                        ) {
                            unset($item->availability[$key]);

                            continue;
                        }
                    }
                }

                if ($item->availability === []) {
                    continue;
                }
            }

            $result[] = $item;
        }

        return $result;
    }
}
