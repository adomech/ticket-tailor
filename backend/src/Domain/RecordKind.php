<?php

declare(strict_types=1);

namespace Payments\Domain;

enum RecordKind: string
{
    case Charge = 'charge';
    case Refund = 'refund';
    case Payout = 'payout';
}
