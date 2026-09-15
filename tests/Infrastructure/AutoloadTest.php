<?php

declare(strict_types=1);

use FiscalIdentifiers\Internal\Package;

covers(Package::class);

it('loads the core through Composer PSR-4 autoloading', function (): void {
    expect(Package::name())->toBe('luiscoutinh/fiscal-identifiers');
});
