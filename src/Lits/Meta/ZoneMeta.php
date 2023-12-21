<?php

declare(strict_types=1);

namespace Lits\Meta;

use Lits\LibCal\Data\Space\ZoneSpaceData;
use Lits\Meta;

final class ZoneMeta extends Meta
{
    public function __construct(public ZoneSpaceData $data)
    {
        $this->id = $data->id;
        $this->setSlug($data->name);
    }
}
