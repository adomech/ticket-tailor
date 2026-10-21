<?php

declare(strict_types=1);

namespace Payments\Http\Request;

use Payments\Domain\Dates;

final readonly class DashboardQuery
{
    public const DEFAULT_DATE = '2026-06-20';
    /** @var list<int> */
    private const PERIODS = [1, 7, 30];

    private function __construct(public string $date, public int $days)
    {
    }

    /** @param array<string, mixed> $query */
    public static function fromArray(array $query): self
    {
        if (array_diff(array_keys($query), ['date', 'days']) !== []) {
            throw new \InvalidArgumentException('query_keys');
        }
        $date = $query['date'] ?? self::DEFAULT_DATE;
        $days = $query['days'] ?? '30';
        if (!is_string($date) || !Dates::valid($date)) {
            throw new \InvalidArgumentException('query_date');
        }
        if (!is_string($days) || !ctype_digit($days) || !in_array((int) $days, self::PERIODS, true)) {
            throw new \InvalidArgumentException('query_days');
        }
        return new self($date, (int) $days);
    }
}
