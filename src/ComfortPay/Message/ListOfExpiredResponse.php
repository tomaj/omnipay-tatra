<?php

declare(strict_types=1);

namespace Omnipay\ComfortPay\Message;

use Omnipay\Common\Message\AbstractResponse;

/**
 * The registered cards that expire in the requested month: a list of ['id' => card id].
 */
final class ListOfExpiredResponse extends AbstractResponse
{
    #[\Override]
    public function isSuccessful(): bool
    {
        return $this->getExpires() !== [];
    }

    /**
     * @return array<array<string, int|string>>
     */
    public function getExpires(): array
    {
        /** @var array<array<string, int|string>> */
        return is_array($this->data) ? $this->data : [];
    }
}
