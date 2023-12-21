<?php

declare(strict_types=1);

namespace Lits\Config;

use Lits\Config;

final class BookConfig extends Config
{
    public bool $narrowDatetime = true;
    public bool $narrowCapacity = true;
    public bool $narrowAccessible = true;
    public bool $narrowPower = true;

    /** @var array<MetaConfig> $locations */
    public array $locations = [];

    /** @var array<MetaConfig> $categories */
    public array $categories = [];

    /** @var array<MetaConfig> $items */
    public array $items = [];

    /** @var array<QuestionConfig> $questions */
    public array $questions = [];
}
