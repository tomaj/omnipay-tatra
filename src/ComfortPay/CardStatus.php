<?php

declare(strict_types=1);

namespace Omnipay\ComfortPay;

/**
 * The status of a registered card returned by checkCard().
 */
enum CardStatus: string
{
    case Ok = 'OK';
    case Fail = 'FAIL';
    case Unknown = 'UNKNOWN';
}
