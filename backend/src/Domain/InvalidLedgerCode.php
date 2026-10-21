<?php

declare(strict_types=1);

namespace Payments\Domain;

enum InvalidLedgerCode: string
{
    case RecordLimit = 'record_limit';
    case RecordShape = 'record_shape';
    case DuplicateConflict = 'duplicate_conflict';
    case RecordType = 'record_type';
    case RecordId = 'record_id';
    case RecordDate = 'record_date';
    case Currency = 'currency';
    case RecordAmount = 'record_amount';
    case ChargeStatus = 'charge_status';
    case OrderRef = 'order_ref';
    case ChargeFees = 'charge_fees';
    case RecordNet = 'record_net';
    case ChargeNet = 'charge_net';
    case RefundFees = 'refund_fees';
    case RefundNet = 'refund_net';
    case RefundReference = 'refund_reference';
    case PayoutStatus = 'payout_status';
    case ArrivalDate = 'arrival_date';
    case ArrivalOrder = 'arrival_order';
    case Membership = 'membership';
    case FeeShape = 'fee_shape';
    case FeeType = 'fee_type';
    case FeeValue = 'fee_value';
    case MissingFee = 'missing_fee';
    case AvailableDate = 'available_date';
    case AvailableOrder = 'available_order';
    case RefundOrder = 'refund_order';
    case RefundLimit = 'refund_limit';
    case RefundCredit = 'refund_credit';
    case PayoutReference = 'payout_reference';
    case DoubleAllocation = 'double_allocation';
    case PayoutOrder = 'payout_order';
    case PayoutAvailability = 'payout_availability';
    case PayoutTotal = 'payout_total';
    case SourceRead = 'source_read';
    case SourceSize = 'source_size';
    case SourceJson = 'source_json';
    case SourceShape = 'source_shape';
    case Reconciliation = 'reconciliation';
}
