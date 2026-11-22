<?php

declare(strict_types=1);

namespace Omnipay\CardPay\Message;

/**
 * The TXN parameter of CardPay's server-to-server transaction request.
 */
enum CancelAuthorizeTxn: string
{
    /** Capture (close) a pre-authorization */
    case ClosePreauthorization = 'CPA';
    /** Reverse (storno) a pre-authorization */
    case StornoPreauthorization = 'SPA';
    /** Refund (chargeback) a captured payment */
    case Chargeback = 'CB';
}
