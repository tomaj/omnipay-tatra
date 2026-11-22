<?php

declare(strict_types=1);

namespace Omnipay\CardPay\Message;

use Omnipay\Core\Message\AbstractCallbackResponse;

/**
 * The verified result of a CardPay pre-authorization.
 */
final class CompleteAuthorizeResponse extends AbstractCallbackResponse
{
    /**
     * The reason code of a declined payment.
     */
    public function getRc(): ?string
    {
        return $this->getDataString('RC');
    }

    /**
     * The 3-D Secure result (TRES).
     */
    public function getTres(): ?string
    {
        return $this->getDataString('TRES');
    }

    /**
     * The registered card id (CID), when the card was registered.
     */
    public function getCid(): ?string
    {
        return $this->getDataString('CID');
    }

    /**
     * The bank's transaction id (TID), needed to capture, reverse or refund the pre-authorization.
     */
    #[\Override]
    public function getTransactionReference(): ?string
    {
        return $this->getDataString('TID');
    }

    /**
     * The masked card number (CC).
     */
    public function getCc(): ?string
    {
        return $this->getDataString('CC');
    }

    /**
     * The approval code (AC).
     */
    public function getAc(): ?string
    {
        return $this->getDataString('AC');
    }
}
