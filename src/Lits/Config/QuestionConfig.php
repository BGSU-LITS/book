<?php

declare(strict_types=1);

namespace Lits\Config;

use Lits\Config;

final class QuestionConfig extends Config
{
    public string $id;
    public string $type;

    public function __construct(string $id, string $type)
    {
        $this->id = $id;
        $this->type = $type;
    }
}
