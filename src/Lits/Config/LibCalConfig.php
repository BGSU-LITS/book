<?php

declare(strict_types=1);

namespace Lits\Config;

use Lits\Config;
use Lits\Exception\InvalidConfigException;

final class LibCalConfig extends Config
{
    public string $host = '';
    public string $clientId = '';
    public string $clientSecret = '';

    /** @throws InvalidConfigException */
    public function test(): void
    {
        if ($this->host === '') {
            throw new InvalidConfigException(
                'The host must be specified'
            );
        }

        if ($this->clientId === '') {
            throw new InvalidConfigException(
                'The clientId must be specified'
            );
        }

        if ($this->clientSecret === '') {
            throw new InvalidConfigException(
                'The clientSecret must be specified'
            );
        }
    }
}
