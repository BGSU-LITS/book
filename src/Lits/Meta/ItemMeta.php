<?php

declare(strict_types=1);

namespace Lits\Meta;

use League\Period\Datepoint;
use League\Period\Exception as PeriodException;
use League\Period\Period;
use Lits\Config\MetaConfig;
use Lits\LibCal\Data\Space\AvailabilitySpaceData;
use Lits\LibCal\Data\Space\ItemSpaceData;
use Lits\Meta;
use Lits\TraitMeta;

final class ItemMeta extends Meta
{
    use TraitMeta;

    public ItemSpaceData $data;
    public \DateInterval $lengthDivisor;

    /** @var array<string,array<string,bool>> */
    public array $times = [];

    /** @param MetaConfig[] $configs */
    public function __construct(ItemSpaceData $data, array $configs)
    {
        $this->id = $data->id;
        $this->data = $data;
        $this->lengthDivisor = new \DateInterval('P15M');
        $this->setSlug($data->name);
        $this->setConfig($configs);
    }

    public function addConfig(MetaConfig $config): void
    {
        if (!\is_null($config->description)) {
            $this->description = $config->description;
        }

        if (!\is_null($config->emailDomain)) {
            $this->emailDomain = $config->emailDomain;
        }

        if (!\is_null($config->lengthDefault)) {
            $this->lengthDefault = $config->lengthDefault;
        }

        if (!\is_null($config->lengthDefault)) {
            $this->lengthMinimum = $config->lengthMinimum;
        }

        if (!\is_null($config->lengthDefault)) {
            $this->lengthMaximum = $config->lengthMaximum;
        }

        if (!\is_null($config->nicknameField)) {
            $this->nicknameField = $config->nicknameField;
        }

        if (!\is_null($config->nicknameRequired)) {
            $this->nicknameRequired = $config->nicknameRequired;
        }
    }

    public function loadPeriod(Period $period): void
    {
        $this->times = [];

        /** @var \DateTimeImmutable $day */
        foreach ($period->dateRangeForward('1 day') as $day) {
            $this->times[$day->format('Y-m-d')] = [];
        }
    }

    /** @param AvailabilitySpaceData[] $availability */
    public function loadAvailability(array $availability): void
    {
        $first = \reset($availability);

        if ($first === false) {
            return;
        }

        $start = $first->from;
        $end = $first->to;
        $set = [];

        foreach ($availability as $available) {
            $time = $available->from->format('H:i');
            $set[$available->from->format('Y-m-d')][$time] = true;

            $datetime = (clone $start)->modify($time);

            if ($start > $datetime) {
                $start = $datetime;
            }

            $datetime = (clone $datetime)->add(
                $this->lengthMinimum ?? $this->lengthDivisor
            );

            if ($end < $datetime) {
                $end = $datetime;
            }
        }

        foreach (\array_keys($this->times) as $date) {
            $start = $start->modify($date);
            $end = $end->modify($date);

            if ($end <= $start) {
                $end = $end->modify('+1 day');
            }

            $period = new Period($start, $end);
            $datePeriod = $period->dateRangeForward($this->lengthDivisor);

            /** @var \DateTimeImmutable $datetime */
            foreach ($datePeriod as $datetime) {
                $time = $datetime->format('H:i');

                $this->times[$date][$time] =
                    isset($set[$datetime->format('Y-m-d')][$time]);
            }
        }
    }

    public function slots(): int
    {
        if (\is_null($this->lengthDefault)) {
            return 1;
        }

        try {
            $period = new Period(
                new Datepoint(),
                (new Datepoint())->add($this->lengthDefault)
            );
        } catch (PeriodException $exception) {
            return 1;
        }

        return \iterator_count(
            $period->dateRangeForward($this->lengthDivisor)
        );
    }
}
