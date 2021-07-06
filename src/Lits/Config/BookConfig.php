<?php

declare(strict_types=1);

namespace Lits\Config;

use Lits\Config;

final class BookConfig extends Config
{
    /** @var MetaConfig[] $locations */
    public array $locations = [];

    /** @var MetaConfig[] $categories */
    public array $categories = [];

    /** @var MetaConfig[] $items */
    public array $items = [];

    /** @var QuestionConfig[] $questions */
    public array $questions = [];
}
