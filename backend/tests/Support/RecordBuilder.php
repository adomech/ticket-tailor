<?php

declare(strict_types=1);

namespace Payments\Tests\Support;

final class RecordBuilder
{
    /** @return array<string, mixed> */
    public static function charge(string $id = 'ch_a', int $tailorFee = 30, int $stripeFee = 59): array
    {
        return [
            'id' => $id, 'object' => 'charge', 'created' => '2026-06-01T10:00:00Z', 'amount' => 1000,
            'currency' => 'gbp', 'status' => 'succeeded', 'order_ref' => 'TT-1',
            'fee_details' => [
                ['type' => 'stripe_fee', 'amount' => $stripeFee],
                ['type' => 'application_fee', 'amount' => $tailorFee],
            ],
            'net' => 1000 - $tailorFee - $stripeFee, 'available_on' => '2026-06-01',
        ];
    }

    /** @return array<string, mixed> */
    public static function refund(
        string $id = 're_a',
        int $amount = 500,
        int $fee = -15,
        string $date = '2026-06-02',
        string $charge = 'ch_a',
    ): array {
        return [
            'id' => $id, 'object' => 'refund', 'created' => $date . 'T10:00:00Z', 'charge' => $charge,
            'amount' => $amount, 'currency' => 'gbp',
            'fee_details' => [['type' => 'application_fee', 'amount' => $fee]],
            'net' => -$amount - $fee, 'available_on' => $date,
        ];
    }

    /**
     * @param list<string> $members
     * @return array<string, mixed>
     */
    public static function payout(
        string $id = 'po_a',
        string $date = '2026-06-04',
        int $amount = 911,
        array $members = ['ch_a'],
        ?string $arrival = null,
    ): array {
        return [
            'id' => $id, 'object' => 'payout', 'created' => $date . 'T10:00:00Z', 'amount' => $amount,
            'currency' => 'gbp', 'status' => 'paid', 'arrival_date' => $arrival ?? $date,
            'included_balance_transactions' => $members,
        ];
    }
}
