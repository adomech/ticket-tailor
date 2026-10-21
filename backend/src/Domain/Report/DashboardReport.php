<?php

declare(strict_types=1);

namespace Payments\Domain\Report;

final readonly class DashboardReport implements \JsonSerializable
{
    /** @param array<string, mixed> $data */
    public function __construct(private array $data)
    {
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
