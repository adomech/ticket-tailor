<?php

declare(strict_types=1);

namespace Payments\Domain;

final class Reconciliation
{
    public static function validate(int $bank, int $transit, int $available, int $later, int $net): void
    {
        InvalidLedger::unless(
            $bank + $transit + $available + $later === $net,
            InvalidLedgerCode::Reconciliation,
        );
    }
}
