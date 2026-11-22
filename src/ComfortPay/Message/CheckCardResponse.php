<?php

declare(strict_types=1);

namespace Omnipay\ComfortPay\Message;

use Omnipay\ComfortPay\CardStatus;
use Omnipay\Common\Message\AbstractResponse;

/**
 * The status of a registered card: OK, FAIL or UNKNOWN.
 */
final class CheckCardResponse extends AbstractResponse
{
    #[\Override]
    public function isSuccessful(): bool
    {
        return $this->getCardStatus() === CardStatus::Ok;
    }

    public function getStatus(): ?string
    {
        $status = is_object($this->data) && property_exists($this->data, 'status') ? $this->data->status : null;

        return is_string($status) ? $status : null;
    }

    public function getCardStatus(): ?CardStatus
    {
        return CardStatus::tryFrom((string) $this->getStatus());
    }
}
