<?php

declare(strict_types=1);

namespace Payments\Infrastructure;

final readonly class Settings
{
    public function __construct(public string $recordsPath, public string $logLevel)
    {
    }

    public static function fromEnvironment(): self
    {
        $path = getenv('RECORDS_PATH');
        $level = getenv('LOG_LEVEL');
        return new self(
            is_string($path) && $path !== '' ? $path : dirname(__DIR__, 3) . '/data/records.json',
            is_string($level) && $level !== '' ? $level : 'warning',
        );
    }
}
