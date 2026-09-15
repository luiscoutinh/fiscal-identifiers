<?php

declare(strict_types=1);

namespace LuisCoutinho\FiscalIdentifiers\Contracts;

use LuisCoutinho\FiscalIdentifiers\FiscalIdentifier;
use LuisCoutinho\FiscalIdentifiers\Results\ExternalVerificationResult;

interface ExternalVerificationProvider
{
    public function key(): string;

    public function verify(FiscalIdentifier $identifier): ExternalVerificationResult;
}
