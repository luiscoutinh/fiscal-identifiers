<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Contracts;

use FiscalIdentifiers\FiscalIdentifier;
use FiscalIdentifiers\Results\ExternalVerificationResult;

interface ExternalVerificationProvider
{
    public function key(): string;

    public function verify(FiscalIdentifier $identifier): ExternalVerificationResult;
}
