<?php

declare(strict_types=1);

namespace Payments\Domain;

use Payments\Domain\Parsing\RecordParser;
use Payments\Domain\Report\DashboardReport;
use Payments\Domain\Report\ReportBuilder;

final class Ledger
{
    /** @var array<string, Record> */
    private array $records = [];
    /** @var array<string, Record> */
    private array $allocations;

    /** @param list<mixed> $rows */
    public function __construct(array $rows)
    {
        InvalidLedger::unless(count($rows) <= 10000, InvalidLedgerCode::RecordLimit);
        $indexed = [];
        foreach ($rows as $row) {
            InvalidLedger::unless(is_array($row) && !array_is_list($row), InvalidLedgerCode::RecordShape);
            $record = RecordParser::parse($row);
            $canonical = self::canonical($row);
            if (isset($indexed[$record->id])) {
                InvalidLedger::unless($indexed[$record->id] === $canonical, InvalidLedgerCode::DuplicateConflict);
                continue;
            }
            $indexed[$record->id] = $canonical;
            $this->records[$record->id] = $record;
        }
        uasort($this->records, static fn (Record $a, Record $b): int =>
            [$a->created, $a->id] <=> [$b->created, $b->id]);
        $this->allocations = LedgerValidator::validate($this->records);
    }

    public function report(string $date, int $days): DashboardReport
    {
        return ReportBuilder::build($this->records, $this->allocations, $date, $days);
    }

    private static function canonical(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        if (!array_is_list($value)) {
            ksort($value);
        }
        return array_map(self::canonical(...), $value);
    }
}
