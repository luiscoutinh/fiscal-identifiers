<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Configuration;

use FiscalIdentifiers\IdentifierSubject;

final readonly class IdentifierResolutionConfiguration
{
    public ?IdentifierSubject $defaultSubject;

    public function __construct(IdentifierSubject|string|null $defaultSubject = null)
    {
        $this->defaultSubject = is_string($defaultSubject)
            ? IdentifierSubject::from($defaultSubject)
            : $defaultSubject;
    }
}
