<?php

declare(strict_types=1);

namespace Payments\Tests\Unit\Domain;

use Payments\Domain\InvalidLedger;
use Payments\Domain\InvalidLedgerCode;
use Payments\Domain\Reconciliation;
use PHPUnit\Framework\TestCase;

final class ReconciliationTest extends TestCase
{
    public function testRejectsAnImbalanceUsingIndependentValues(): void
    {
        $this->expectException(InvalidLedger::class);
        $this->expectExceptionMessage(InvalidLedgerCode::Reconciliation->value);
        Reconciliation::validate(44_536, 0, 54_327, 0, 98_864);
    }
}
