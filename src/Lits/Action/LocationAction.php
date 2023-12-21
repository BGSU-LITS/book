<?php

declare(strict_types=1);

namespace Lits\Action;

use Lits\Action;
use Lits\Config\BookConfig;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;

final class LocationAction extends Action
{
    use TraitBookLocation;

    /**
     * @throws HttpBadRequestException
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
        $context['location'] = $this->findLocation();

        // Load all items for maximum capacity and nearest availability.
        $context['location']->loadItems(
            $this->getItems($context['location']->id),
            $this->settings['book']->items,
        );

        // Reload items if there is a query to narrow list.
        if ($context['query'] !== []) {
            $context['location']->loadItems(
                $this->getItems($context['location']->id, $context['query']),
                $this->settings['book']->items,
            );
        }

        $context['location']->loadCategories(
            $this->getCategories($context['location']->id),
            $this->settings['book']->categories,
        );

        $context['location']->loadZones(
            $this->getZones($context['location']->id),
        );

        try {
            $this->render($this->template(), $context);
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception,
            );
        }
    }
}
