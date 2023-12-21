<?php

declare(strict_types=1);

namespace Lits\Config;

use Lits\Config;
use Lits\TraitMeta;

final class MetaConfig extends Config
{
    use TraitMeta;

    public function __construct(public int $id)
    {
    }
}
