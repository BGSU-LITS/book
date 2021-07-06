<?php

declare(strict_types=1);

namespace Lits\Meta;

use Lits\Config\MetaConfig;
use Lits\LibCal\Data\Space\CategorySpaceData;
use Lits\Meta;

final class CategoryMeta extends Meta
{
    public CategorySpaceData $data;

    /** @param MetaConfig[] $configs */
    public function __construct(CategorySpaceData $data, array $configs)
    {
        $this->id = $data->cid;
        $this->data = $data;
        $this->setSlug($data->name);
        $this->setConfig($configs);
    }
}
