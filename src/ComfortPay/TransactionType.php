<?php

declare(strict_types=1);

namespace Omnipay\ComfortPay;

/**
 * The transactionType of a ComfortPay charge (doCardTransaction).
 */
enum TransactionType: string
{
    case Purchase = 'PURCHASE';
    case Preauthorization = 'PREAUTHORIZATION';
    case PreauthConfirm = 'PREAUTH-CONFIRM';
    case PreauthCancel = 'PREAUTH-CANCEL';
    case Chargeback = 'CHARGEBACK';

    /**
     * Whether the transaction refers to an earlier one (parentTransactionId is required).
     */
    public function requiresParentTransaction(): bool
    {
        return match ($this) {
            self::PreauthConfirm, self::PreauthCancel, self::Chargeback => true,
            self::Purchase, self::Preauthorization => false,
        };
    }
}
