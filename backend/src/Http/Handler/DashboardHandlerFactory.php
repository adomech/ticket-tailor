<?php

declare(strict_types=1);

namespace Payments\Http\Handler;

use Payments\Application\GetDashboardReport;
use Payments\Infrastructure\JsonSource;
use Payments\Infrastructure\Settings;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class DashboardHandlerFactory
{
    public function __invoke(ContainerInterface $container): DashboardHandler
    {
        return new DashboardHandler(
            new GetDashboardReport(new JsonSource($container->get(Settings::class)->recordsPath)),
            $container->get(LoggerInterface::class),
        );
    }
}
