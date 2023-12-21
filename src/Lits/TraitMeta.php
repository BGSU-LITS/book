<?php

declare(strict_types=1);

namespace Lits;

trait TraitMeta
{
    public ?string $description = null;
    public ?string $emailDomain = null;
    public bool $grouped = true;
    public \DateInterval|false $lengthDefault = false;
    public \DateInterval|false $lengthMinimum = false;
    public \DateInterval|false $lengthMaximum = false;
    public ?string $nicknameField = null;
    public ?bool $nicknameRequired = null;
}
