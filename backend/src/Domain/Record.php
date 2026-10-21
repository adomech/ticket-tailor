<?php

declare(strict_types=1);

namespace Payments\Domain;

final readonly class Record
{
    /** @param list<string>|null $transactionIds */
    public function __construct(
        public string $id,
        public RecordKind $kind,
        public string $created,
        public int $amount,
        public ?int $net,
        public ?int $tailorFee,
        public ?int $stripeFee,
        public ?string $availableOn,
        public ?string $reference,
        public ?string $arrivalDate,
        public ?array $transactionIds,
    ) {
    }
}
