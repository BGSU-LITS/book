<?php

declare(strict_types=1);

namespace Lits\Action;

use Lits\Action;
use Lits\Config\BookConfig;
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
        if (!($this->settings['book'] instanceof BookConfig)) {
            throw new HttpInternalServerErrorException($this->request);
        }

        $context = [];
        $context['query'] = $this->request->getQueryParams();
        $context['locations'] = [];

        try {
            $locations = $this->client->space()
                ->locations()
                ->send();
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception
            );
        }

        foreach ($locations as $location) {
            if (!isset($context['query']['private']) && !$location->public) {
                continue;
            }

            $meta = new LocationMeta(
                $location,
                $this->settings['book']->locations
            );

            $meta->loadItems(
                $this->getItems($meta->id, $context['query']),
                $this->settings['book']->items
            );

            $meta->loadCategories(
                $this->getCategories($meta->id),
                $this->settings['book']->categories
            );

            $meta->loadZones($this->getZones($meta->id));
            $context['locations'][] = $meta;
        }

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
}
