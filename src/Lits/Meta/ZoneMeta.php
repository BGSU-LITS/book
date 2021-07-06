<?php

declare(strict_types=1);

namespace Lits\Meta;

use Lits\LibCal\Data\Space\ZoneSpaceData;
use Lits\Meta;

final class ZoneMeta extends Meta
{
    public ZoneSpaceData $data;

    public function __construct(ZoneSpaceData $data)
    {
        $this->id = $data->id;
        $this->data = $data;
        $this->setSlug($data->name);
    }
}
