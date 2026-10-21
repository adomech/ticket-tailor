<?php

declare(strict_types=1);

namespace Payments\Tests\Integration;

use Laminas\Diactoros\ServerRequest;
use Payments\Application\GetDashboardReport;
use Payments\Http\Handler\DashboardHandler;
use Payments\Infrastructure\JsonSource;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class DashboardContractTest extends TestCase
{
    public function testFixtureResponseMatchesCommittedContract(): void
    {
        $source = new JsonSource(dirname(__DIR__, 3) . '/data/records.json');
        $handler = new DashboardHandler(new GetDashboardReport($source), new NullLogger());
        $actual = json_decode((string) $handler->handle(new ServerRequest())->getBody(), true);
        $contract = dirname(__DIR__, 3) . '/docs/fixtures/dashboard.json';
        $expected = json_decode((string) file_get_contents($contract), true);
        self::assertSame($expected, $actual);
    }
}
