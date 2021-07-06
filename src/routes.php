<?php

declare(strict_types=1);

use Lits\Action\IdAction;
use Lits\Action\IndexAction;
use Lits\Action\ItemAction;
use Lits\Action\LocationAction;
use Lits\Action\TimeAction;
use Lits\Framework;

return function (Framework $framework): void {
    $framework->app()
        ->get('/', IndexAction::class)
        ->setName('index');

    $framework->app()
        ->map(['GET', 'POST'], '/id/{id}', IdAction::class)
        ->setName('id');

    $framework->app()
        ->get('/{location}', LocationAction::class)
        ->setName('location');

    $date = '{date:\d{4}-\d{2}-\d{2}}';
    $time = '{time:\d{2}:\d{2}}';

    $framework->app()
        ->get('/{location}/{item}[/' . $date . ']', ItemAction::class)
        ->setName('item');

    $framework->app()
        ->map(
            ['GET', 'POST'],
            '/{location}/{item}/' . $date . '/' . $time,
            TimeAction::class
        )
        ->setName('time');
};
