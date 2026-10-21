<?php

declare(strict_types=1);

namespace Payments\Domain;

use DateTimeImmutable;
use DateTimeZone;

final class Dates
{
    public static function valid(string $value, bool $timestamp = false): bool
    {
        $format = $timestamp ? 'Y-m-d\TH:i:s\Z' : 'Y-m-d';
        $date = DateTimeImmutable::createFromFormat('!' . $format, $value, new DateTimeZone('UTC'));
        return $date !== false && $date->format($format) === $value && substr($value, 0, 4) >= '2000';
    }

    public static function start(string $end, int $days): string
    {
        return (new DateTimeImmutable($end, new DateTimeZone('UTC')))
            ->modify('-' . ($days - 1) . ' days')->format('Y-m-d');
    }
}
