<?php

declare(strict_types=1);

namespace Omnipay\CardPay\Message;

use Omnipay\Common\Message\AbstractResponse;

/**
 * The bank's answer to a capture, reversal or refund: `res` OK or FAIL, and an `errorCode` when it failed.
 */
final class CancelAuthorizeResponse extends AbstractResponse
{
    public const string SUCCESS = 'OK';

    #[\Override]
    public function isSuccessful(): bool
    {
        return $this->getRes() === self::SUCCESS;
    }

    public function getRes(): ?string
    {
        return $this->getDataString('res');
    }

    public function getErrorCode(): ?string
    {
        return $this->getDataString('errorCode');
    }

    #[\Override]
    public function getCode(): ?string
    {
        return $this->getErrorCode();
    }

    private function getDataString(string $key): ?string
    {
        if (!is_array($this->data) || !isset($this->data[$key])) {
            return null;
        }

        return is_string($this->data[$key]) ? $this->data[$key] : null;
    }
}
