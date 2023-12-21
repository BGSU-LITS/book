<?php

declare(strict_types=1);

namespace Lits\Meta;

use League\Period\Period;
use Lits\Config\MetaConfig;
use Lits\LibCal\Data\Space\AvailabilitySpaceData;
use Lits\LibCal\Data\Space\ItemSpaceData;
use Lits\Meta;
use Lits\TraitMeta;
use Safe\DateTimeImmutable;

final class ItemMeta extends Meta
{
    use TraitMeta;

    public \DateInterval $lengthDivisor;

    /** @var array<string,array<string,bool>> */
    public array $times = [];

    /**
     * @param array<MetaConfig> $configs
     * @throws \Exception Length divisor could not be created.
     */
    public function __construct(public ItemSpaceData $data, array $configs)
    {
        $this->id = $data->id;
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

        $this->addConfigLength($config);
        $this->addConfigNickname($config);
    }

    public function loadPeriod(Period $period): void
    {
        $this->times = [];

        foreach ($period->rangeForward('1 day') as $day) {
            $this->times[$day->format('Y-m-d')] = [];
        }
    }

    /** @param array<AvailabilitySpaceData> $availability */
    public function loadAvailability(array $availability): void
    {
        $first = \reset($availability);

        if ($first === false) {
            return;
        }

        $this->loadAvailabilityFirst($first, $availability);
    }

    public function lengthDefault(): \DateInterval
    {
        if ($this->lengthDefault instanceof \DateInterval) {
            return $this->lengthDefault;
        }

        return $this->lengthDivisor;
    }

    public function lengthMaximum(): \DateInterval
    {
        if ($this->lengthMaximum instanceof \DateInterval) {
            return $this->lengthMaximum;
        }

        return $this->lengthDivisor;
    }

    public function lengthMinimum(): \DateInterval
    {
        if ($this->lengthMinimum instanceof \DateInterval) {
            return $this->lengthMinimum;
        }

        return $this->lengthDivisor;
    }

    /** @throws \Exception */
    public function slots(): int
    {
        if (!$this->lengthDefault instanceof \DateInterval) {
            return 1;
        }

        $period = Period::fromDate(
            new DateTimeImmutable(),
            (new DateTimeImmutable())->add($this->lengthDefault),
        );

        return \iterator_count($period->rangeForward($this->lengthDivisor));
    }

    private function addConfigLength(MetaConfig $config): void
    {
        if ($config->lengthDefault instanceof \DateInterval) {
            $this->lengthDefault = $config->lengthDefault;
        }

        if ($config->lengthMinimum instanceof \DateInterval) {
            $this->lengthMinimum = $config->lengthMinimum;
        }

        if ($config->lengthMaximum instanceof \DateInterval) {
            $this->lengthMaximum = $config->lengthMaximum;
        }
    }

    private function addConfigNickname(MetaConfig $config): void
    {
        if (!\is_null($config->nicknameField)) {
            $this->nicknameField = $config->nicknameField;
        }

        if (!\is_null($config->nicknameRequired)) {
            $this->nicknameRequired = $config->nicknameRequired;
        }
    }

    /** @param array<AvailabilitySpaceData> $availability */
    private function loadAvailabilityFirst(
        AvailabilitySpaceData $first,
        array $availability,
    ): void {
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

            $datetime = (clone $datetime)->add($this->lengthMinimum());

            if ($end < $datetime) {
                $end = $datetime;
            }
        }

        $this->loadAvailabilityTimes($start, $end, $set);
    }

    /** @param array<string,array<string,bool>> $set */
    private function loadAvailabilityTimes(
        \DateTime $start,
        \DateTime $end,
        array $set,
    ): void {
        foreach (\array_keys($this->times) as $date) {
            $start = $start->modify($date);
            $end = $end->modify($date);

            if ($end <= $start) {
                $end = $end->modify('+1 day');
            }

            $period = Period::fromDate($start, $end)
                ->rangeForward($this->lengthDivisor);

            foreach ($period as $datetime) {
                $time = $datetime->format('H:i');

                $this->times[$date][$time] =
                    isset($set[$datetime->format('Y-m-d')][$time]);
            }
        }
    }
}
