<?php

declare(strict_types=1);

namespace Payments\Domain\Parsing;

use Payments\Domain\Dates;
use Payments\Domain\InvalidLedger;
use Payments\Domain\InvalidLedgerCode;
use Payments\Domain\RecordKind;

final class RecordFields
{
    /**
     * @param array<string, mixed> $row
     * @return array{int, int}
     */
    public static function fees(array $row, bool $stripeRequired): array
    {
        $fees = $row['fee_details'] ?? null;
        InvalidLedger::unless(is_array($fees) && array_is_list($fees), InvalidLedgerCode::FeeShape);
        $values = ['application_fee' => null, 'stripe_fee' => null];
        foreach ($fees as $fee) {
            InvalidLedger::unless(is_array($fee) && count($fee) === 2, InvalidLedgerCode::FeeShape);
            $type = $fee['type'] ?? null;
            InvalidLedger::unless(in_array($type, ['application_fee', 'stripe_fee'], true), InvalidLedgerCode::FeeType);
            InvalidLedger::unless(
                $values[$type] === null && is_int($fee['amount'] ?? null),
                InvalidLedgerCode::FeeValue,
            );
            $values[$type] = $fee['amount'];
        }
        InvalidLedger::unless($values['application_fee'] !== null, InvalidLedgerCode::MissingFee);
        InvalidLedger::unless(!$stripeRequired || $values['stripe_fee'] !== null, InvalidLedgerCode::ChargeFees);
        return [$values['application_fee'], $values['stripe_fee'] ?? 0];
    }

    /** @param array<string, mixed> $row */
    public static function availableDate(array $row, string $created): string
    {
        $available = self::string($row, 'available_on', InvalidLedgerCode::AvailableDate);
        InvalidLedger::unless(Dates::valid($available), InvalidLedgerCode::AvailableDate);
        InvalidLedger::unless($available >= substr($created, 0, 10), InvalidLedgerCode::AvailableOrder);
        return $available;
    }

    /** @param array<string, mixed> $row */
    public static function validateKeys(array $row, RecordKind $kind): void
    {
        $extra = match ($kind) {
            RecordKind::Charge => ['status', 'order_ref', 'fee_details', 'net', 'available_on'],
            RecordKind::Refund => ['charge', 'fee_details', 'net', 'available_on'],
            RecordKind::Payout => ['status', 'arrival_date', 'included_balance_transactions'],
        };
        $keys = array_keys($row);
        $expected = array_merge(['id', 'object', 'created', 'amount', 'currency'], $extra);
        sort($keys);
        sort($expected);
        InvalidLedger::unless($keys === $expected, InvalidLedgerCode::RecordShape);
    }

    /** @param array<string, mixed> $row */
    public static function string(array $row, string $key, InvalidLedgerCode $code): string
    {
        InvalidLedger::unless(is_string($row[$key] ?? null), $code);
        return $row[$key];
    }

    /** @param array<string, mixed> $row */
    public static function money(array $row, string $key, InvalidLedgerCode $code): int
    {
        $value = $row[$key] ?? null;
        InvalidLedger::unless(is_int($value) && abs($value) <= 1_000_000_000, $code);
        return $value;
    }
}
