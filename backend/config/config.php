<?php

declare(strict_types=1);

use Laminas\ConfigAggregator\ConfigAggregator;

$aggregator = new ConfigAggregator([
    new Laminas\Diactoros\ConfigProvider(),
    new Mezzio\ConfigProvider(),
    new Mezzio\Router\ConfigProvider(),
    new Mezzio\Router\FastRouteRouter\ConfigProvider(),
    new Payments\ConfigProvider(),
    require __DIR__ . '/autoload/global.php',
]);

return $aggregator->getMergedConfig();
