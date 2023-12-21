<?php

declare(strict_types=1);

namespace Lits\Config;

use Lits\Config;

final class QuestionConfig extends Config
{
    public function __construct(public string $id, public string $type)
    {
    }
}
