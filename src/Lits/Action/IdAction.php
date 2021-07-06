<?php

declare(strict_types=1);

namespace Lits\Action;

use Cocur\Slugify\Slugify;
use Lits\Action;
use Lits\Config\BookConfig;
use Lits\LibCal\Exception\NotFoundException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;

final class IdAction extends Action
{
    use TraitBookTime;

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

        if (!isset($this->data['id'])) {
            throw new HttpBadRequestException($this->request);
        }

        $context = [];

        try {
            $result = $this->client->space()
                ->booking($this->data['id'])
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

        $context['booking'] = \reset($result);

        if ($context['booking'] === false) {
            throw new HttpNotFoundException($this->request);
        }

        $slugify = new Slugify();

        $this->data['location'] = $slugify->slugify(
            $context['booking']->location_name
        );

        $this->data['item'] = $slugify->slugify(
            $context['booking']->item_name
        );

        $post = $this->request->getParsedBody();

        if (\is_array($post) && isset($post['cancel'])) {
            try {
                $result = $this->client->space()
                    ->cancel($this->data['id'])
                    ->send();

                $this->redirect(
                    $this->routeCollector->getRouteParser()->urlFor('item', [
                        'location' => $this->data['location'],
                        'item' => $this->data['item'],
                    ])
                );

                $response = \reset($result);

                if ($response === false || !$response->cancelled) {
                    $this->message(
                        'failure',
                        'The booking could not be cancelled.'
                    );

                    return;
                }

                $this->message(
                    'success',
                    'The previous booking has been cancelled.'
                );

                return;
            } catch (\Throwable $exception) {
                throw new HttpInternalServerErrorException(
                    $this->request,
                    null,
                    $exception
                );
            }
        }

        $context['location'] = $this->findLocation();
        $context['location']->loadItems(
            $this->getItems($context['location']->id),
            $this->settings['book']->items
        );

        $context['item'] = $this->findItem($context['location']);
        $context['time'] = self::hoursAndMinutes(
            $context['booking']->toDate->diff($context['booking']->fromDate)
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
}
