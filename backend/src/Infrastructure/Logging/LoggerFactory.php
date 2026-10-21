<?php

declare(strict_types=1);

namespace Payments\Infrastructure\Logging;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Payments\Infrastructure\Settings;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class LoggerFactory
{
    public function __invoke(ContainerInterface $container): LoggerInterface
    {
        $level = match (strtolower($container->get(Settings::class)->logLevel)) {
            'debug' => Level::Debug, 'info' => Level::Info, 'notice' => Level::Notice,
            'error' => Level::Error, 'critical' => Level::Critical, 'alert' => Level::Alert,
            'emergency' => Level::Emergency, default => Level::Warning,
        };
        return new Logger('payments', [new StreamHandler('php://stderr', $level)]);
    }
}
