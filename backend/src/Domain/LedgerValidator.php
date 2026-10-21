<?php

declare(strict_types=1);

namespace Payments\Domain;

final class LedgerValidator
{
    /**
     * @param array<string, Record> $records
     * @return array<string, Record>
     */
    public static function validate(array $records): array
    {
        self::refunds($records);
        return self::payouts($records);
    }

    /** @param array<string, Record> $records */
    private static function refunds(array $records): void
    {
        $refunded = $credited = [];
        foreach ($records as $record) {
            if ($record->kind !== RecordKind::Refund) {
                continue;
            }
            $reference = $record->reference;
            $charge = $reference === null ? null : ($records[$reference] ?? null);
            InvalidLedger::unless($charge?->kind === RecordKind::Charge, InvalidLedgerCode::RefundReference);
            InvalidLedger::unless($record->created >= $charge->created, InvalidLedgerCode::RefundOrder);
            $refunded[$charge->id] = ($refunded[$charge->id] ?? 0) + $record->amount;
            InvalidLedger::unless($refunded[$charge->id] <= $charge->amount, InvalidLedgerCode::RefundLimit);
            $fee = $charge->tailorFee;
            InvalidLedger::unless($fee !== null && $record->tailorFee !== null, InvalidLedgerCode::RecordShape);
            $expected = intdiv($fee * $refunded[$charge->id] + intdiv($charge->amount, 2), $charge->amount);
            $previous = $credited[$charge->id] ?? 0;
            $credit = -$record->tailorFee;
            InvalidLedger::unless($credit === $expected - $previous, InvalidLedgerCode::RefundCredit);
            $credited[$charge->id] = $previous + $credit;
        }
    }

    /**
     * @param array<string, Record> $records
     * @return array<string, Record>
     */
    private static function payouts(array $records): array
    {
        $allocations = [];
        foreach ($records as $record) {
            if ($record->kind !== RecordKind::Payout) {
                continue;
            }
            $total = 0;
            foreach ($record->transactionIds ?? [] as $id) {
                $movement = $records[$id] ?? null;
                InvalidLedger::unless(
                    $movement !== null && $movement->kind !== RecordKind::Payout,
                    InvalidLedgerCode::PayoutReference,
                );
                InvalidLedger::unless(!isset($allocations[$id]), InvalidLedgerCode::DoubleAllocation);
                InvalidLedger::unless($movement->created <= $record->created, InvalidLedgerCode::PayoutOrder);
                InvalidLedger::unless(
                    $movement->availableOn !== null
                        && $movement->availableOn <= substr($record->created, 0, 10),
                    InvalidLedgerCode::PayoutAvailability,
                );
                InvalidLedger::unless($movement->net !== null, InvalidLedgerCode::RecordShape);
                $total += $movement->net;
                $allocations[$id] = $record;
            }
            InvalidLedger::unless($total === $record->amount, InvalidLedgerCode::PayoutTotal);
        }
        return $allocations;
    }
}
