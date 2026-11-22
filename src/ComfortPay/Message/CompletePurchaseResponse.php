<?php

declare(strict_types=1);

namespace Omnipay\ComfortPay\Message;

use Omnipay\Core\Message\AbstractCallbackResponse;

/**
 * The verified result of a ComfortPay payment.
 */
final class CompletePurchaseResponse extends AbstractCallbackResponse
{
    /**
     * The id of the registered card (CID): pass it as referedCardId to charge().
     */
    public function getCid(): ?string
    {
        return $this->getDataString('CID');
    }

    /**
     * The masked card number (CC).
     */
    public function getCc(): ?string
    {
        return $this->getDataString('CC');
    }

    /**
     * The 3-D Secure result (TRES).
     */
    public function getTres(): ?string
    {
        return $this->getDataString('TRES');
    }

    /**
     * The reason code of a declined payment (RC).
     */
    public function getRc(): ?string
    {
        return $this->getDataString('RC');
    }
}
