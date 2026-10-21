<?php

declare(strict_types=1);

namespace Payments\Domain;

final class InvalidLedger extends \RuntimeException
{
    public function __construct(public readonly InvalidLedgerCode $ledgerCode)
    {
        parent::__construct($ledgerCode->value);
    }

    /** @phpstan-assert true $condition */
    public static function unless(bool $condition, InvalidLedgerCode $code): void
    {
        if (!$condition) {
            throw new self($code);
        }
    }
}
