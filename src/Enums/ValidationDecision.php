<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Enums;

enum ValidationDecision: string
{
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
