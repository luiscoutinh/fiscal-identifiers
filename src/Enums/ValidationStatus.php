<?php

declare(strict_types=1);

namespace FiscalIdentifiers\Enums;

enum ValidationStatus: string
{
    case Passed = 'passed';
    case Failed = 'failed';
    case NotSupported = 'not_supported';
}
