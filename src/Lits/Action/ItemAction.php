<?php

declare(strict_types=1);

namespace Lits\Action;

use League\Period\DatePoint;
use League\Period\Period;
use Lits\Action;
use Lits\Config\BookConfig;
use Lits\Meta\ItemMeta;
use Safe\DateTimeImmutable;
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
            $this->getItems($context['location']->id, ['seats' => true]),
            $this->settings['book']->items,
        );

        $context['item'] = $this->findItem($context['location']);
        $context['item']->loadPeriod($this->findPeriod($context['item']));

        $availability = $this->getAvailability(
            $context['item']->id,
            \array_key_first($context['item']->times),
            \array_key_last($context['item']->times),
            true,
        );

        $this->setDivisor($context['item'], $availability);
        $context['item']->loadAvailability($availability);
        $context['category'] = $this->findCategory($context['item']);

        foreach (['location', 'category', 'item'] as $type) {
            if (!\is_null($context[$type]->config)) {
                $context['item']->addConfig($context[$type]->config);
            }
        }

        try {
            $context['slots'] = $context['item']->slots();

            $this->render($this->template(), $context);
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception,
            );
        }
    }

    /** @throws HttpInternalServerErrorException */
    private function findPeriod(ItemMeta $item): Period
    {
        /** @var ?string $date */
        $date = $this->request->getQueryParam('date');

        if (\is_null($date)) {
            $date = $this->findPeriodDate($item);
        }

        try {
            // ISO weeks start on Mondays, so move the requested day one day
            // forward to calculate the correct week, and then move the range
            // back one day so it starts on a Sunday.
            $datetime = (new DateTimeImmutable($date))
                ->add(new \DateInterval('P1D'));

            return DatePoint::fromDate($datetime)->isoWeek()->move('-1 day');
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception,
            );
        }
    }

    private function findPeriodDate(ItemMeta $item): string
    {
        if (isset($this->data['date']) && $this->data['date'] !== '') {
            return $this->data['date'];
        }

        if (isset($item->data->availability[0])) {
            return $item->data->availability[0]->from->format('Y-m-d');
        }

        return 'today';
    }
}
