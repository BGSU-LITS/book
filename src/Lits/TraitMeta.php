<?php

declare(strict_types=1);

namespace Lits;

trait TraitMeta
{
    public ?string $emailDomain = null;
    public ?\DateInterval $lengthDefault = null;
    public ?\DateInterval $lengthMinimum = null;
    public ?\DateInterval $lengthMaximum = null;
}
