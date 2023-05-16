<?php

declare(strict_types=1);

namespace Lits\Action;

use League\Period\Datepoint;
use Lits\Action;
use Lits\Config\BookConfig;
use Lits\LibCal\Data\Space\Reserve\PayloadReserveSpaceData;
use Lits\Meta\LocationMeta;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;

final class TimeAction extends Action
{
    use TraitBookTime;

    /**
     * @throws HttpBadRequestException
     * @throws HttpInternalServerErrorException
     * @throws HttpNotFoundException
     */
    public function action(): void
    {
        $context = [];
        $context['post'] = $this->request->getParsedBody();

        if (\is_array($context['post'])) {
            $this->postData($context['post']);

            return;
        }

        $context['location'] = $this->loadLocation();
        $context['datepoint'] = $this->loadDatepoint();
        $context['item'] = $this->findItem($context['location']);
        $context['item']->loadPeriod($context['datepoint']->day());

        $availability = $this->getAvailability(
            $context['item']->id,
            \array_key_first($context['item']->times),
            \array_key_last($context['item']->times)
        );

        $this->setDivisor($context['item'], $availability);
        $context['item']->loadAvailability($availability);
        $context['category'] = $this->findCategory($context['item']);

        foreach (['location', 'category', 'item'] as $type) {
            if (!\is_null($context[$type]->config)) {
                $context['item']->addConfig($context[$type]->config);
            }
        }

        $context['form'] = $this->findForm(
            $context['location'],
            $context['category'],
            $context['item']
        );

        $context['options'] = $this->findOptions(
            $context['item'],
            $context['datepoint']
        );

        $context['email'] = $this->findEmail($context['item']);

        $to = $context['datepoint'];
        $to = $to->add(
            $context['item']->lengthDefault ?? $context['item']->lengthDivisor
        );

        $context['post'] = [
            'start' => $context['datepoint']->format('c'),
            'bookings' => [[
                'id' => $context['item']->id,
                'to' => $to->format('c'),
            ]],
        ];

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

    /** @throws HttpBadRequestException */
    private function loadDatepoint(): Datepoint
    {
        if (!isset($this->data['date']) || !isset($this->data['time'])) {
            throw new HttpBadRequestException($this->request);
        }

        return Datepoint::create(
            $this->data['date'] . ' ' . $this->data['time']
        );
    }

    /**
     * @throws HttpBadRequestException
     * @throws HttpInternalServerErrorException
     * @throws HttpNotFoundException
     */
    private function loadLocation(): LocationMeta
    {
        if (!($this->settings['book'] instanceof BookConfig)) {
            throw new HttpInternalServerErrorException($this->request);
        }

        $location = $this->findLocation();
        $location->loadItems(
            $this->getItems($location->id, ['seats' => true]),
            $this->settings['book']->items
        );

        return $location;
    }

    /**
     * @param mixed[] $post
     * @throws HttpInternalServerErrorException
     */
    private function postData(array $post): void
    {
        try {
            $data = PayloadReserveSpaceData::fromArray($post);

            if (\is_string($data->nickname)) {
                $data->nickname .= ' - ';
            } else {
                $data->nickname = '';
            }

            $data->nickname .= $data->fname . ' ' . $data->lname;

            $response = $this->client->space()
                ->reserve($data)
                ->send();

            $this->redirect(
                $this->routeCollector->getRouteParser()
                    ->urlFor('id', ['id' => $response->booking_id])
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
