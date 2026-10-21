<?php

declare(strict_types=1);

namespace Payments\Tests\Unit\Domain;

use Payments\Domain\InvalidLedger;
use Payments\Domain\InvalidLedgerCode;
use Payments\Domain\Ledger;
use Payments\Tests\Support\RecordBuilder;
use PHPUnit\Framework\TestCase;

final class LedgerTest extends TestCase
{
    /** @return list<array<string, mixed>> */
    private function fixture(): array
    {
        $decoded = json_decode((string) file_get_contents(dirname(__DIR__, 4) . '/data/records.json'), true);
        self::assertIsArray($decoded);
        self::assertTrue(array_is_list($decoded));
        $rows = [];
        foreach ($decoded as $row) {
            self::assertIsArray($row);
            $rows[] = $row;
        }
        return $rows;
    }

    public function testCompleteFixtureHasIndependentlyCalculatedTotals(): void
    {
        $report = (new Ledger($this->fixture()))->report('2026-06-20', 30)->jsonSerialize();
        self::assertSame(['gross' => 116250, 'refunds' => 10750, 'tailorFees' => 3805,
            'stripeFees' => 2831, 'feeCredits' => 320, 'net' => 98864], $report['earnings']);
        self::assertSame(['bank' => 44536, 'transit' => 0, 'sent' => 44536,
            'remaining' => 54328, 'available' => 54328, 'later' => 0], $report['balances']);
        self::assertSame([
            ['date' => '2026-06-16', 'amount' => 7962, 'available' => true],
            ['date' => '2026-06-17', 'amount' => 50746, 'available' => true],
            ['date' => '2026-06-20', 'amount' => -4380, 'available' => true],
        ], $report['availability']);
    }

    public function testOrderAndIdenticalDuplicateDeliveriesDoNotChangeTotals(): void
    {
        $rows = $this->fixture();
        shuffle($rows);
        $report = (new Ledger($rows))->report('2026-06-20', 30)->jsonSerialize();
        self::assertSame(98864, $report['earnings']['net']);
    }

    public function testPropertyOrderDoesNotCreateAConflict(): void
    {
        $rows = $this->fixture();
        $rows[] = array_reverse($rows[0], true);
        $report = (new Ledger($rows))->report('2026-06-20', 30)->jsonSerialize();
        self::assertSame(98864, $report['earnings']['net']);
    }

    public function testConflictingDuplicateFailsTheLedger(): void
    {
        $rows = $this->fixture();
        $copy = $rows[0];
        $copy['created'] = '2026-06-15T16:41:00Z';
        $rows[] = $copy;
        $this->expectExceptionMessage(InvalidLedgerCode::DuplicateConflict->value);
        new Ledger($rows);
    }

    public function testRefundCreditAndRetainedStripeFee(): void
    {
        $report = (new Ledger([RecordBuilder::charge('ch_a'), RecordBuilder::refund('re_a', 500, -15)]))
            ->report('2026-06-02', 7)->jsonSerialize();
        self::assertSame(15, $report['earnings']['tailorFees']);
        self::assertSame(59, $report['earnings']['stripeFees']);
        self::assertSame(426, $report['earnings']['net']);
    }

    public function testRefundCreditsUseCumulativeRounding(): void
    {
        $rows = [
            RecordBuilder::charge('ch_a', 1, 0),
            RecordBuilder::refund('re_a', 500, -1),
            RecordBuilder::refund('re_b', 500, 0, '2026-06-03'),
        ];
        $report = (new Ledger($rows))->report('2026-06-03', 7)->jsonSerialize();
        self::assertSame(1, $report['earnings']['feeCredits']);
        self::assertSame(0, $report['earnings']['tailorFees']);
        self::assertSame(0, $report['earnings']['net']);
    }

    public function testMalformedRecordMakesLedgerUnavailable(): void
    {
        $malformed = RecordBuilder::charge('ch_bad');
        unset($malformed['available_on']);

        $this->expectException(InvalidLedger::class);
        $this->expectExceptionMessage(InvalidLedgerCode::RecordShape->value);
        new Ledger([$malformed]);
    }

    public function testInvalidRecordDateMakesLedgerUnavailable(): void
    {
        $malformed = RecordBuilder::charge('ch_bad');
        $malformed['created'] = 'not-a-date';

        $this->expectException(InvalidLedger::class);
        $this->expectExceptionMessage(InvalidLedgerCode::RecordDate->value);
        new Ledger([$malformed]);
    }

    public function testNonObjectRecordMakesLedgerUnavailable(): void
    {
        $this->expectException(InvalidLedger::class);
        $this->expectExceptionMessage(InvalidLedgerCode::RecordShape->value);
        new Ledger(['invalid']);
    }

    public function testCumulativeRefundLimitIsEnforced(): void
    {
        $this->expectExceptionMessage(InvalidLedgerCode::RefundLimit->value);
        new Ledger([
            RecordBuilder::charge('ch_a'),
            RecordBuilder::refund('re_a', 600, -18),
            RecordBuilder::refund('re_b', 500, -15),
        ]);
    }

    public function testPayoutCannotDoubleAllocate(): void
    {
        $this->expectExceptionMessage(InvalidLedgerCode::DoubleAllocation->value);
        new Ledger([
            RecordBuilder::charge('ch_a'),
            RecordBuilder::payout('po_a', '2026-06-04'),
            RecordBuilder::payout('po_b', '2026-06-05'),
        ]);
    }

    public function testPayoutCannotPrecedeAvailability(): void
    {
        $charge = RecordBuilder::charge('ch_a');
        $charge['available_on'] = '2026-06-10';
        $this->expectExceptionMessage(InvalidLedgerCode::PayoutAvailability->value);
        new Ledger([$charge, RecordBuilder::payout('po_a', '2026-06-09')]);
    }

    public function testPostPayoutRefundRemainsAVisibleDebit(): void
    {
        $rows = [
            RecordBuilder::charge('ch_a'),
            RecordBuilder::payout('po_a', '2026-06-04'),
            RecordBuilder::refund('re_a', 1000, -30, '2026-06-06'),
        ];
        $report = (new Ledger($rows))->report('2026-06-06', 1)->jsonSerialize();
        self::assertSame(-970, $report['balances']['remaining']);
        self::assertSame(-970, $report['earnings']['net']);
    }

    public function testUtcPeriodBoundariesAreInclusive(): void
    {
        $report = (new Ledger($this->fixture()))->report('2026-06-20', 7)->jsonSerialize();
        self::assertSame('2026-06-14', $report['start']);
        self::assertSame(60350, $report['earnings']['gross']);
        self::assertSame(46366, $report['earnings']['net']);
        $day = (new Ledger($this->fixture()))->report('2026-06-20', 1)->jsonSerialize();
        self::assertSame(-4380, $day['earnings']['net']);
    }

    public function testUtcMidnightIsIncluded(): void
    {
        $charge = RecordBuilder::charge();
        $charge['created'] = '2026-06-01T00:00:00Z';
        $report = (new Ledger([$charge]))->report('2026-06-01', 1)->jsonSerialize();
        self::assertSame(911, $report['earnings']['net']);
    }

    public function testPayoutCreatedOnReportingDateCanBeInTransit(): void
    {
        $records = [
            RecordBuilder::charge(),
            RecordBuilder::payout(date: '2026-06-04', arrival: '2026-06-05'),
        ];
        $report = (new Ledger($records))->report('2026-06-04', 7)->jsonSerialize();
        self::assertSame(0, $report['balances']['bank']);
        self::assertSame(911, $report['balances']['transit']);
        self::assertSame('in_transit', $report['payouts'][0]['state']);
    }
}
