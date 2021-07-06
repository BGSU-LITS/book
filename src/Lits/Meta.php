<?php

declare(strict_types=1);

namespace Lits;

use Cocur\Slugify\Slugify;
use Lits\Config\MetaConfig;

abstract class Meta
{
    public int $id;
    public ?string $slug = null;
    public ?MetaConfig $config = null;

    public function setSlug(?string $name = null): void
    {
        if (\is_string($name)) {
            $this->slug = (new Slugify())->slugify($name);
        }
    }

    /** @param MetaConfig[] $configs */
    public function setConfig(array $configs): void
    {
        foreach ($configs as $config) {
            if ($config->id === $this->id) {
                $this->config = $config;
            }
        }
    }
}
