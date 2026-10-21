<?php

declare(strict_types=1);

namespace Payments\Domain\Report;

use Payments\Domain\Dates;
use Payments\Domain\InvalidLedger;
use Payments\Domain\InvalidLedgerCode;
use Payments\Domain\Reconciliation;
use Payments\Domain\Record;
use Payments\Domain\RecordKind;

final class ReportBuilder
{
    /**
     * @param array<string, Record> $records
     * @param array<string, Record> $allocations
     */
    public static function build(array $records, array $allocations, string $date, int $days): DashboardReport
    {
        $start = Dates::start($date, $days);
        $earnings = self::totals();
        $lifetime = self::totals();
        $remaining = $transactions = $payouts = [];
        $bank = $transit = 0;
        foreach ($records as $record) {
            if (substr($record->created, 0, 10) > $date) {
                continue;
            }
            if ($record->kind === RecordKind::Payout) {
                $arrival = $record->arrivalDate;
                InvalidLedger::unless($arrival !== null, InvalidLedgerCode::ArrivalDate);
                $arrived = $arrival <= $date;
                $bank += $arrived ? $record->amount : 0;
                $transit += $arrived ? 0 : $record->amount;
                $payouts[] = ['id' => $record->id, 'amount' => $record->amount,
                    'sent' => substr($record->created, 0, 10), 'arrival' => $arrival,
                    'state' => $arrived ? 'arrived' : 'in_transit'];
                continue;
            }
            self::add($lifetime, $record);
            InvalidLedger::unless(
                $record->availableOn !== null && $record->net !== null,
                InvalidLedgerCode::RecordShape,
            );
            $allocation = $allocations[$record->id] ?? null;
            if ($allocation === null || substr($allocation->created, 0, 10) > $date) {
                $remaining[$record->availableOn] = ($remaining[$record->availableOn] ?? 0) + $record->net;
            }
            if (substr($record->created, 0, 10) >= $start) {
                self::add($earnings, $record);
                $transactions[] = self::transaction($record);
            }
        }
        [$availability, $available, $later] = self::availability($remaining, $date);
        Reconciliation::validate($bank, $transit, $available, $later, $lifetime['net']);
        return new DashboardReport([
            'reportingDate' => $date, 'start' => $start, 'days' => $days, 'currency' => 'GBP',
            'earnings' => $earnings, 'lifetime' => $lifetime,
            'balances' => ['bank' => $bank, 'transit' => $transit, 'sent' => $bank + $transit,
                'remaining' => $available + $later, 'available' => $available, 'later' => $later],
            'availability' => $availability, 'payouts' => array_reverse($payouts),
            'transactions' => array_reverse($transactions),
        ]);
    }

    /** @return array{gross: int, refunds: int, tailorFees: int, stripeFees: int, feeCredits: int, net: int} */
    private static function totals(): array
    {
        return ['gross' => 0, 'refunds' => 0, 'tailorFees' => 0, 'stripeFees' => 0, 'feeCredits' => 0, 'net' => 0];
    }

    /** @param array<string, int> $totals */
    private static function add(array &$totals, Record $record): void
    {
        InvalidLedger::unless($record->net !== null && $record->tailorFee !== null
            && $record->stripeFee !== null, InvalidLedgerCode::RecordShape);
        $totals['gross'] += $record->kind === RecordKind::Charge ? $record->amount : 0;
        $totals['refunds'] += $record->kind === RecordKind::Refund ? $record->amount : 0;
        $totals['tailorFees'] += $record->tailorFee;
        $totals['stripeFees'] += $record->stripeFee;
        $totals['feeCredits'] += $record->kind === RecordKind::Refund ? -$record->tailorFee : 0;
        $totals['net'] += $record->net;
    }

    /** @return array<string, int|string> */
    private static function transaction(Record $record): array
    {
        InvalidLedger::unless($record->net !== null && $record->tailorFee !== null
            && $record->stripeFee !== null && $record->availableOn !== null
            && $record->reference !== null, InvalidLedgerCode::RecordShape);
        return ['id' => $record->id, 'kind' => $record->kind->value, 'created' => $record->created,
            'amount' => $record->amount, 'net' => $record->net, 'tailorFee' => $record->tailorFee,
            'stripeFee' => $record->stripeFee, 'available' => $record->availableOn,
            'reference' => $record->reference];
    }

    /**
     * @param array<string, int> $remaining
     * @return array{list<array{date: string, amount: int, available: bool}>, int, int}
     */
    private static function availability(array $remaining, string $date): array
    {
        ksort($remaining);
        $groups = [];
        $available = $later = 0;
        foreach ($remaining as $day => $amount) {
            $isAvailable = $day <= $date;
            $groups[] = ['date' => $day, 'amount' => $amount, 'available' => $isAvailable];
            $available += $isAvailable ? $amount : 0;
            $later += $isAvailable ? 0 : $amount;
        }
        return [$groups, $available, $later];
    }
}
