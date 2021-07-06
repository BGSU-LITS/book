<?php

declare(strict_types=1);

use Lits\Framework;
use Lits\Package\ProjectPackage;

require_once dirname(__DIR__) .
    DIRECTORY_SEPARATOR . 'vendor' .
    DIRECTORY_SEPARATOR . 'autoload.php';

return new Framework([
    new ProjectPackage(),
]);
