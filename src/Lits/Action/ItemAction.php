<?php

declare(strict_types=1);

namespace Lits\Action;

use League\Period\Datepoint;
use Lits\Action;
use Lits\Config\BookConfig;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;

final class ItemAction extends Action
{
    use TraitBookItem;

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

        $context['location']->loadItems(
            $this->getItems($context['location']->id),
            $this->settings['book']->items
        );

        $context['item'] = $this->findItem($context['location']);

        /** @var ?string $date */
        $date = $this->request->getQueryParam('date');

        if (\is_null($date)) {
            if (isset($this->data['date']) && $this->data['date'] !== '') {
                $date = $this->data['date'];
            } elseif (isset($context['item']->data->availability[0])) {
                $date = $context['item']->data->availability[0]
                    ->from->format('Y-m-d');
            } else {
                $date = 'today';
            }
        }

        // ISO weeks start on Mondays, so move the requested day one day
        // forward to calculate the correct week, and then move the range
        // back one day so it starts on a Sunday.
        $context['item']->loadPeriod(
            Datepoint::create($date)
                ->modify('+1 day')
                ->getIsoWeek()
                ->move('-1 day')
        );

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

        $context['slots'] = $context['item']->slots();

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
