<?php

declare(strict_types=1);

namespace Payments\Application;

use Payments\Domain\Ledger;
use Payments\Domain\RecordSource;
use Payments\Domain\Report\DashboardReport;
use Payments\Http\Request\DashboardQuery;

final readonly class GetDashboardReport
{
    public function __construct(private RecordSource $source)
    {
    }

    public function execute(DashboardQuery $query): DashboardReport
    {
        return (new Ledger($this->source->load()))->report($query->date, $query->days);
    }
}
