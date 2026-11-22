<?php

declare(strict_types=1);

namespace Omnipay\TatraPay\Message;

use Omnipay\Core\Message\AbstractCallbackResponse;

/**
 * The verified result of a TatraPay payment: RES is OK (paid), FAIL or TOUT (timeout).
 */
final class CompletePurchaseResponse extends AbstractCallbackResponse
{
    public const string TIMEOUT = 'TOUT';

    /**
     * True when TatraPay reported a timeout (RES=TOUT): the result of the payment is not known yet, the bank confirms
     * it later.
     */
    #[\Override]
    public function isPending(): bool
    {
        return $this->getRes() === self::TIMEOUT;
    }
}
