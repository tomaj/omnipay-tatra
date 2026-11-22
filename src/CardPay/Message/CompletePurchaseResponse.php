<?php

declare(strict_types=1);

namespace Omnipay\CardPay\Message;

use Omnipay\Core\Message\AbstractCallbackResponse;

/**
 * The verified result of a CardPay payment.
 */
final class CompletePurchaseResponse extends AbstractCallbackResponse
{
    /**
     * The reason code of a declined payment (returned when Tatra banka enabled it for the merchant).
     */
    public function getRc(): ?string
    {
        return $this->getDataString('RC');
    }
}
