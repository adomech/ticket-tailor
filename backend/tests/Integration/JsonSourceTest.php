<?php

declare(strict_types=1);

namespace Payments\Tests\Integration;

use Payments\Domain\InvalidLedger;
use Payments\Domain\InvalidLedgerCode;
use Payments\Infrastructure\JsonSource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class JsonSourceTest extends TestCase
{
    /** @param callable(): string $contents */
    #[DataProvider('invalidSources')]
    public function testRejectsInvalidSources(InvalidLedgerCode $code, callable $contents): void
    {
        $path = tempnam(sys_get_temp_dir(), 'ledger-');
        self::assertIsString($path);
        file_put_contents($path, $contents());
        try {
            $this->expectException(InvalidLedger::class);
            $this->expectExceptionMessage($code->value);
            (new JsonSource($path))->load();
        } finally {
            unlink($path);
        }
    }

    /** @return iterable<string, array{InvalidLedgerCode, callable(): string}> */
    public static function invalidSources(): iterable
    {
        yield 'size' => [InvalidLedgerCode::SourceSize, static fn (): string => str_repeat(' ', 2_000_001)];
        yield 'json' => [InvalidLedgerCode::SourceJson, static fn (): string => '{'];
        yield 'shape' => [InvalidLedgerCode::SourceShape, static fn (): string => '{"record":true}'];
    }

    public function testRejectsUnreadableSource(): void
    {
        $this->expectExceptionMessage(InvalidLedgerCode::SourceRead->value);
        (new JsonSource('/does/not/exist'))->load();
    }
}
