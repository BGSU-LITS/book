<?php

declare(strict_types=1);

namespace Lits\Action;

use Lits\Action;
use Lits\Config\BookConfig;
use Lits\LibCal\Data\Space\LocationSpaceData;
use Lits\Meta\LocationMeta;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;

final class IndexAction extends Action
{
    use TraitBookLocation;

    /**
     * @throws HttpInternalServerErrorException
     * @throws HttpNotFoundException
     */
    public function action(): void
    {
        try {
            $locations = $this->client->space()
                ->locations()
                ->cache()
                ->send();
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception
            );
        }

        $context = [];
        $context['query'] = $this->request->getQueryParams();
        $context['locations'] = $this->findLocations(
            $locations,
            $context['query']
        );

        try {
            $this->render($this->template(), $context);
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception
            );
        }
    }

    /**
     * @param LocationSpaceData[] $locations
     * @param mixed[] $query
     * @return LocationMeta[]
     * @throws HttpInternalServerErrorException
     */
    private function findLocations(array $locations, array $query): array
    {
        if (!($this->settings['book'] instanceof BookConfig)) {
            throw new HttpInternalServerErrorException($this->request);
        }

        $result = [];

        foreach ($locations as $location) {
            if (!isset($query['private']) && !$location->public) {
                continue;
            }

            $meta = new LocationMeta(
                $location,
                $this->settings['book']->locations
            );

            $meta->loadItems(
                $this->getItems($meta->id, $query),
                $this->settings['book']->items
            );

            $meta->loadCategories(
                $this->getCategories($meta->id),
                $this->settings['book']->categories
            );

            $meta->loadZones($this->getZones($meta->id));
            $result[] = $meta;
        }

        return $result;
    }
}
