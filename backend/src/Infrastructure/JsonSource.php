<?php

declare(strict_types=1);

namespace Payments\Infrastructure;

use Payments\Domain\InvalidLedger;
use Payments\Domain\InvalidLedgerCode;
use Payments\Domain\RecordSource;

final readonly class JsonSource implements RecordSource
{
    private const MAX_BYTES = 2_000_000;
    private const MAX_RECORDS = 10_000;

    public function __construct(private string $path)
    {
    }

    /** @return list<mixed> */
    public function load(): array
    {
        $stream = @fopen($this->path, 'rb');
        if ($stream === false) {
            throw new InvalidLedger(InvalidLedgerCode::SourceRead);
        }
        try {
            $json = stream_get_contents($stream, self::MAX_BYTES + 1);
        } finally {
            fclose($stream);
        }
        InvalidLedger::unless(is_string($json) && strlen($json) <= self::MAX_BYTES, InvalidLedgerCode::SourceSize);
        try {
            $rows = json_decode($json, true, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new InvalidLedger(InvalidLedgerCode::SourceJson);
        }
        InvalidLedger::unless(
            is_array($rows) && array_is_list($rows) && count($rows) <= self::MAX_RECORDS,
            InvalidLedgerCode::SourceShape,
        );
        return $rows;
    }
}
