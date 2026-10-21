<?php

declare(strict_types=1);

namespace Payments\Tests\Integration;

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Mezzio\Application;
use Payments\Tests\Support\ApplicationFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class ApplicationTest extends TestCase
{
    public function testUnknownApiRouteReturnsNotFound(): void
    {
        $response = $this->app()->handle($this->request('GET', '/api/missing'));

        self::assertSame(404, $response->getStatusCode());
        $this->assertErrorBody($response->getBody()->__toString(), 'Not found.');
    }

    public function testUnsupportedMethodReturnsMethodNotAllowed(): void
    {
        $response = $this->app()->handle($this->request('POST', '/api/dashboard'));

        self::assertSame(405, $response->getStatusCode());
        self::assertStringContainsString('GET', $response->getHeaderLine('Allow'));
        $this->assertErrorBody($response->getBody()->__toString(), 'Method not allowed.');
    }

    public function testErrorHandlerConvertsUnhandledExceptionToGenericResponse(): void
    {
        $app = $this->app();
        $app->get('/api/failure', static function (): never {
            throw new \RuntimeException('sensitive detail');
        });

        $response = $app->handle($this->request('GET', '/api/failure'));

        self::assertSame(500, $response->getStatusCode());
        self::assertStringNotContainsString('sensitive detail', (string) $response->getBody());
        self::assertSame('no-store', $response->getHeaderLine('Cache-Control'));
        $this->assertErrorBody((string) $response->getBody(), 'An unexpected error occurred.');
    }

    private function app(): Application
    {
        return ApplicationFactory::create(new NullLogger());
    }

    private function request(string $method, string $path): ServerRequest
    {
        return (new ServerRequest())->withMethod($method)->withUri(new Uri($path));
    }

    private function assertErrorBody(string $json, string $message): void
    {
        $body = json_decode($json, true);
        self::assertIsArray($body);
        self::assertSame($message, $body['message']);
        self::assertIsString($body['reference']);
        self::assertNotSame('', $body['reference']);
    }
}
