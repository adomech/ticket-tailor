<?php

declare(strict_types=1);

namespace Payments\Tests\Unit\Domain;

use Payments\Domain\InvalidLedger;
use Payments\Domain\InvalidLedgerCode;
use Payments\Domain\Ledger;
use Payments\Tests\Support\RecordBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LedgerValidationTest extends TestCase
{
    /** @param list<mixed> $records */
    #[DataProvider('invalidLedgers')]
    public function testRejectsInvalidLedgers(InvalidLedgerCode $code, array $records): void
    {
        $this->expectException(InvalidLedger::class);
        $this->expectExceptionMessage($code->value);
        new Ledger($records);
    }

    /** @return iterable<string, array{InvalidLedgerCode, list<mixed>}> */
    public static function invalidLedgers(): iterable
    {
        yield 'record shape' => [InvalidLedgerCode::RecordShape, ['bad']];
        $changed = RecordBuilder::charge();
        $changed['amount'] = 900;
        $changed['net'] = 811;
        yield 'duplicate conflict' => [InvalidLedgerCode::DuplicateConflict, [RecordBuilder::charge(), $changed]];
        yield 'refund reference' => [InvalidLedgerCode::RefundReference, [RecordBuilder::refund(charge: 'ch_missing')]];
        yield 'refund order' => [InvalidLedgerCode::RefundOrder, [
            RecordBuilder::charge(), RecordBuilder::refund(date: '2026-05-31'),
        ]];
        yield 'refund limit' => [InvalidLedgerCode::RefundLimit, [
            RecordBuilder::charge(), RecordBuilder::refund(amount: 1001, fee: -30),
        ]];
        yield 'refund credit' => [InvalidLedgerCode::RefundCredit, [
            RecordBuilder::charge(), RecordBuilder::refund(fee: -14),
        ]];
        yield 'payout reference' => [InvalidLedgerCode::PayoutReference, [
            RecordBuilder::payout(members: ['ch_missing']),
        ]];
        yield 'double allocation' => [InvalidLedgerCode::DoubleAllocation, [
            RecordBuilder::charge(), RecordBuilder::payout(), RecordBuilder::payout('po_b', '2026-06-05'),
        ]];
        yield 'payout order' => [InvalidLedgerCode::PayoutOrder, [
            RecordBuilder::charge(), RecordBuilder::payout(date: '2026-05-31'),
        ]];
        $charge = RecordBuilder::charge();
        $charge['available_on'] = '2026-06-05';
        yield 'payout availability' => [InvalidLedgerCode::PayoutAvailability, [
            $charge, RecordBuilder::payout(),
        ]];
        yield 'payout total' => [InvalidLedgerCode::PayoutTotal, [
            RecordBuilder::charge(), RecordBuilder::payout(amount: 912),
        ]];
    }

    public function testRejectsTooManyRecords(): void
    {
        $this->expectExceptionMessage(InvalidLedgerCode::RecordLimit->value);
        new Ledger(array_fill(0, 10001, RecordBuilder::charge()));
    }
}
