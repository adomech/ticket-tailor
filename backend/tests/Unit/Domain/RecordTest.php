<?php

declare(strict_types=1);

namespace Payments\Tests\Unit\Domain;

use Payments\Domain\InvalidLedger;
use Payments\Domain\InvalidLedgerCode;
use Payments\Domain\Parsing\RecordParser;
use Payments\Tests\Support\RecordBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RecordTest extends TestCase
{
    /** @param callable(array<string, mixed>): array<string, mixed> $change */
    #[DataProvider('invalidRecords')]
    public function testRejectsInvalidRecords(InvalidLedgerCode $code, callable $change, string $kind = 'charge'): void
    {
        $record = match ($kind) {
            'refund' => RecordBuilder::refund(),
            'payout' => RecordBuilder::payout(),
            default => RecordBuilder::charge(),
        };
        $this->expectException(InvalidLedger::class);
        $this->expectExceptionMessage($code->value);
        RecordParser::parse($change($record));
    }

    /**
     * @return iterable<string, array{
     *   InvalidLedgerCode,
     *   callable(array<string, mixed>): array<string, mixed>,
     *   2?: string
     * }>
     */
    public static function invalidRecords(): iterable
    {
        yield 'shape' => [InvalidLedgerCode::RecordShape, self::set('extra', true)];
        yield 'typed field' => [InvalidLedgerCode::RecordType, self::set('id', 1)];
        yield 'id' => [InvalidLedgerCode::RecordId, self::set('id', 'bad')];
        yield 'created' => [InvalidLedgerCode::RecordDate, self::set('created', 'bad')];
        yield 'currency' => [InvalidLedgerCode::Currency, self::set('currency', 'usd')];
        yield 'amount' => [InvalidLedgerCode::RecordAmount, self::set('amount', 0)];
        yield 'charge status' => [InvalidLedgerCode::ChargeStatus, self::set('status', 'failed')];
        yield 'order reference' => [InvalidLedgerCode::OrderRef, self::set('order_ref', str_repeat('x', 101))];
        yield 'charge fees' => [InvalidLedgerCode::ChargeFees, self::set('fee_details', [
            ['type' => 'stripe_fee', 'amount' => 59], ['type' => 'application_fee', 'amount' => -1],
        ])];
        yield 'record net' => [InvalidLedgerCode::RecordNet, self::set('net', '911')];
        yield 'charge net' => [InvalidLedgerCode::ChargeNet, self::set('net', 900)];
        yield 'fee shape' => [InvalidLedgerCode::FeeShape, self::set('fee_details', ['bad' => true])];
        yield 'fee type' => [InvalidLedgerCode::FeeType, self::set('fee_details', [
            ['type' => 'other', 'amount' => 59], ['type' => 'application_fee', 'amount' => 30],
        ])];
        yield 'fee value' => [InvalidLedgerCode::FeeValue, self::set('fee_details', [
            ['type' => 'stripe_fee', 'amount' => '59'], ['type' => 'application_fee', 'amount' => 30],
        ])];
        yield 'missing fee' => [InvalidLedgerCode::MissingFee, self::set('fee_details', [
            ['type' => 'stripe_fee', 'amount' => 59],
        ])];
        yield 'available date' => [InvalidLedgerCode::AvailableDate, self::set('available_on', 'bad')];
        yield 'available order' => [InvalidLedgerCode::AvailableOrder, self::set('available_on', '2026-05-31')];
        yield 'refund fees' => [InvalidLedgerCode::RefundFees, self::set('fee_details', [
            ['type' => 'application_fee', 'amount' => 15],
        ]), 'refund'];
        yield 'refund net' => [InvalidLedgerCode::RefundNet, self::set('net', -500), 'refund'];
        yield 'refund reference' => [InvalidLedgerCode::RefundReference, self::set('charge', null), 'refund'];
        yield 'payout status' => [InvalidLedgerCode::PayoutStatus, self::set('status', 'failed'), 'payout'];
        yield 'arrival date' => [InvalidLedgerCode::ArrivalDate, self::set('arrival_date', 'bad'), 'payout'];
        yield 'arrival order' => [InvalidLedgerCode::ArrivalOrder, self::set('arrival_date', '2026-06-03'), 'payout'];
        yield 'membership' => [InvalidLedgerCode::Membership, self::set('included_balance_transactions', []), 'payout'];
    }

    /** @return callable(array<string, mixed>): array<string, mixed> */
    private static function set(string $key, mixed $value): callable
    {
        return static function (array $record) use ($key, $value): array {
            $record[$key] = $value;
            return $record;
        };
    }
}
