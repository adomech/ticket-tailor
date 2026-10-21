<?php

declare(strict_types=1);

use Payments\Infrastructure\Settings;

return static fn (): array => [
    'dependencies' => ['services' => [Settings::class => Settings::fromEnvironment()]],
];
