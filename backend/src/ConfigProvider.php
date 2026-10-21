<?php

declare(strict_types=1);

namespace Payments;

use Laminas\Stratigility\Middleware\ErrorHandler;
use Payments\Http\Error\ErrorHandlerFactory;
use Payments\Http\Error\JsonNotFoundHandler;
use Payments\Http\Handler\DashboardHandler;
use Payments\Http\Handler\DashboardHandlerFactory;
use Payments\Http\Middleware\JsonMethodNotAllowedMiddleware;
use Payments\Infrastructure\Logging\LoggerFactory;
use Psr\Log\LoggerInterface;

final class ConfigProvider
{
    /** @return array<string, mixed> */
    public function __invoke(): array
    {
        return ['dependencies' => [
            'factories' => [
                DashboardHandler::class => DashboardHandlerFactory::class,
                ErrorHandler::class => ErrorHandlerFactory::class,
                LoggerInterface::class => LoggerFactory::class,
            ],
            'invokables' => [
                JsonMethodNotAllowedMiddleware::class => JsonMethodNotAllowedMiddleware::class,
                JsonNotFoundHandler::class => JsonNotFoundHandler::class,
            ],
        ]];
    }
}
