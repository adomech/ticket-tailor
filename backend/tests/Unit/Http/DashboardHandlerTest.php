<?php

declare(strict_types=1);

namespace Payments\Tests\Unit\Http;

use Laminas\Diactoros\ServerRequest;
use Payments\Application\GetDashboardReport;
use Payments\Domain\RecordSource;
use Payments\Http\Handler\DashboardHandler;
use Payments\Infrastructure\JsonSource;
use Payments\Tests\Support\RecordBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class DashboardHandlerTest extends TestCase
{
    public function testInvalidQueryReturnsSafeResponse(): void
    {
        $source = new class implements RecordSource {
            public function load(): array
            {
                throw new \LogicException('must not load');
            }
        };
        $handler = new DashboardHandler(new GetDashboardReport($source), new NullLogger());
        $response = $handler->handle((new ServerRequest())->withQueryParams(['days' => '365']));
        self::assertSame(400, $response->getStatusCode());
        self::assertStringNotContainsString('/unused', (string) $response->getBody());
        self::assertSame('no-store', $response->getHeaderLine('Cache-Control'));
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('Choose a valid date and period.', $body['message']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{16}$/', $body['reference']);
        self::assertCount(2, $body);
    }

    public function testInvalidSourceReturnsGenericErrorAndReference(): void
    {
        $handler = new DashboardHandler(new GetDashboardReport(new JsonSource('/missing')), new NullLogger());
        $response = $handler->handle(new ServerRequest());
        self::assertSame(503, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame('Payments are unavailable. Please try again.', $body['message']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{16}$/', $body['reference']);
        self::assertStringNotContainsString('/missing', (string) $response->getBody());
        self::assertStringNotContainsString('source_read', (string) $response->getBody());
    }

    public function testUsesInjectedRecordSource(): void
    {
        $source = new class implements RecordSource {
            public function load(): array
            {
                return [RecordBuilder::charge()];
            }
        };
        $handler = new DashboardHandler(new GetDashboardReport($source), new NullLogger());
        $response = $handler->handle(new ServerRequest());
        $body = json_decode((string) $response->getBody(), true);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame(911, $body['earnings']['net']);
    }

    public function testRequestCannotSelectRecordSourcePath(): void
    {
        $source = new class implements RecordSource {
            public function load(): array
            {
                throw new \LogicException('request selected source');
            }
        };
        $request = (new ServerRequest())->withQueryParams(['recordsPath' => '/etc/passwd']);
        $response = (new DashboardHandler(new GetDashboardReport($source), new NullLogger()))->handle($request);
        self::assertSame(400, $response->getStatusCode());
        self::assertStringNotContainsString('/etc/passwd', (string) $response->getBody());
    }
}
