<?php

declare(strict_types=1);

namespace Lits\Action;

use League\Period\Datepoint;
use Lits\Action;
use Lits\Config\BookConfig;
use Lits\LibCal\Data\Space\Reserve\PayloadReserveSpaceData;
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
        if (!($this->settings['book'] instanceof BookConfig)) {
            throw new HttpInternalServerErrorException($this->request);
        }

        if (!isset($this->data['date']) || !isset($this->data['time'])) {
            throw new HttpBadRequestException($this->request);
        }

        $context = [];
        $context['post'] = $this->request->getParsedBody();

        if (\is_array($context['post'])) {
            try {
                $data = PayloadReserveSpaceData::fromArray($context['post']);

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

            return;
        }

        $context['location'] = $this->findLocation();
        $context['location']->loadItems(
            $this->getItems($context['location']->id),
            $this->settings['book']->items
        );

        $context['datepoint'] = Datepoint::create(
            $this->data['date'] . ' ' . $this->data['time']
        );

        $context['item'] = $this->findItem($context['location']);
        $context['item']->loadPeriod($context['datepoint']->getDay());

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
}
