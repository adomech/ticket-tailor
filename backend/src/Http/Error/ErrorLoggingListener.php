<?php

declare(strict_types=1);

namespace Payments\Http\Error;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final readonly class ErrorLoggingListener
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(
        \Throwable $error,
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): void {
        $this->logger->error('unhandled_http_error', [
            'reference' => $response->getHeaderLine('X-Request-Reference'),
            'exception' => $error::class,
        ]);
    }
}
