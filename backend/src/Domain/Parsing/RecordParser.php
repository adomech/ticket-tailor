<?php

declare(strict_types=1);

namespace Payments\Domain\Parsing;

use Payments\Domain\Dates;
use Payments\Domain\InvalidLedger;
use Payments\Domain\InvalidLedgerCode;
use Payments\Domain\Record;
use Payments\Domain\RecordKind;

final class RecordParser
{
    /** @param array<string, mixed> $row */
    public static function parse(array $row): Record
    {
        $kind = RecordKind::tryFrom(is_string($row['object'] ?? null) ? $row['object'] : '');
        InvalidLedger::unless($kind !== null, InvalidLedgerCode::RecordShape);
        RecordFields::validateKeys($row, $kind);
        $id = RecordFields::string($row, 'id', InvalidLedgerCode::RecordType);
        $created = RecordFields::string($row, 'created', InvalidLedgerCode::RecordType);
        InvalidLedger::unless((bool) preg_match('/^[a-z]{2}_[a-zA-Z0-9_]{1,64}$/D', $id), InvalidLedgerCode::RecordId);
        InvalidLedger::unless(Dates::valid($created, true), InvalidLedgerCode::RecordDate);
        InvalidLedger::unless(
            RecordFields::string($row, 'currency', InvalidLedgerCode::RecordType) === 'gbp',
            InvalidLedgerCode::Currency,
        );
        $amount = RecordFields::money($row, 'amount', InvalidLedgerCode::RecordAmount);
        InvalidLedger::unless($amount > 0, InvalidLedgerCode::RecordAmount);
        return match ($kind) {
            RecordKind::Charge => self::charge($row, $id, $created, $amount),
            RecordKind::Refund => self::refund($row, $id, $created, $amount),
            RecordKind::Payout => self::payout($row, $id, $created, $amount),
        };
    }

    /** @param array<string, mixed> $row */
    private static function charge(array $row, string $id, string $created, int $amount): Record
    {
        [$tailor, $stripe] = RecordFields::fees($row, true);
        InvalidLedger::unless(($row['status'] ?? null) === 'succeeded', InvalidLedgerCode::ChargeStatus);
        $reference = RecordFields::string($row, 'order_ref', InvalidLedgerCode::OrderRef);
        InvalidLedger::unless(strlen($reference) <= 100, InvalidLedgerCode::OrderRef);
        InvalidLedger::unless(
            $tailor >= 0 && $stripe >= 0 && $tailor + $stripe <= $amount,
            InvalidLedgerCode::ChargeFees,
        );
        $net = RecordFields::money($row, 'net', InvalidLedgerCode::RecordNet);
        InvalidLedger::unless($net === $amount - $tailor - $stripe, InvalidLedgerCode::ChargeNet);
        return new Record(
            $id,
            RecordKind::Charge,
            $created,
            $amount,
            $net,
            $tailor,
            $stripe,
            RecordFields::availableDate($row, $created),
            $reference,
            null,
            null
        );
    }

    /** @param array<string, mixed> $row */
    private static function refund(array $row, string $id, string $created, int $amount): Record
    {
        [$tailor, $stripe] = RecordFields::fees($row, false);
        InvalidLedger::unless($tailor <= 0 && $stripe === 0, InvalidLedgerCode::RefundFees);
        $net = RecordFields::money($row, 'net', InvalidLedgerCode::RecordNet);
        InvalidLedger::unless($net === -$amount - $tailor, InvalidLedgerCode::RefundNet);
        return new Record(
            $id,
            RecordKind::Refund,
            $created,
            $amount,
            $net,
            $tailor,
            0,
            RecordFields::availableDate($row, $created),
            RecordFields::string($row, 'charge', InvalidLedgerCode::RefundReference),
            null,
            null
        );
    }

    /** @param array<string, mixed> $row */
    private static function payout(array $row, string $id, string $created, int $amount): Record
    {
        InvalidLedger::unless(($row['status'] ?? null) === 'paid', InvalidLedgerCode::PayoutStatus);
        $arrival = RecordFields::string($row, 'arrival_date', InvalidLedgerCode::ArrivalDate);
        InvalidLedger::unless(Dates::valid($arrival), InvalidLedgerCode::ArrivalDate);
        InvalidLedger::unless($arrival >= substr($created, 0, 10), InvalidLedgerCode::ArrivalOrder);
        $members = $row['included_balance_transactions'] ?? null;
        InvalidLedger::unless(
            is_array($members) && array_is_list($members) && $members !== [],
            InvalidLedgerCode::Membership,
        );
        foreach ($members as $member) {
            InvalidLedger::unless(is_string($member), InvalidLedgerCode::Membership);
        }
        /** @var list<string> $members */
        return new Record(
            $id,
            RecordKind::Payout,
            $created,
            $amount,
            null,
            null,
            null,
            null,
            null,
            $arrival,
            $members
        );
    }
}
