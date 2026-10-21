<?php

declare(strict_types=1);

namespace Payments\Tests\Unit\Http;

use Payments\Http\Request\DashboardQuery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DashboardQueryTest extends TestCase
{
    /** @param array<string, mixed> $query */
    #[DataProvider('invalidQueries')]
    public function testRejectsInvalidQueries(string $code, array $query): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($code);
        DashboardQuery::fromArray($query);
    }

    /** @return iterable<string, array{string, array<string, mixed>}> */
    public static function invalidQueries(): iterable
    {
        yield 'keys' => ['query_keys', ['unknown' => 'value']];
        yield 'date' => ['query_date', ['date' => '2026-02-30']];
        yield 'date below limit' => ['query_date', ['date' => '1999-12-31']];
        yield 'date above limit' => ['query_date', ['date' => '10000-01-01']];
        yield 'days' => ['query_days', ['days' => '2']];
    }

    public function testOwnsDefaults(): void
    {
        $query = DashboardQuery::fromArray([]);
        self::assertSame(DashboardQuery::DEFAULT_DATE, $query->date);
        self::assertSame(30, $query->days);
    }

    public function testAcceptsEveryInclusiveWindow(): void
    {
        foreach ([1, 7, 30] as $days) {
            self::assertSame($days, DashboardQuery::fromArray(['days' => (string) $days])->days);
        }
    }
}
