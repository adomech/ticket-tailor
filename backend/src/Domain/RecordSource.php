<?php

declare(strict_types=1);

namespace Payments\Domain;

interface RecordSource
{
    /** @return list<mixed> */
    public function load(): array;
}
