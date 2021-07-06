<?php

declare(strict_types=1);

namespace Lits\Config;

use Lits\Config;
use Lits\TraitMeta;

final class MetaConfig extends Config
{
    use TraitMeta;

    public int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }
}
