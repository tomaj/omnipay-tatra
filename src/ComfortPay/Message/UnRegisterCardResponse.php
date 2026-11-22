<?php

declare(strict_types=1);

namespace Omnipay\ComfortPay\Message;

use Omnipay\Common\Message\AbstractResponse;

/**
 * The bank answers a removed card with an empty unRegisterCardResponse; a refusal comes as a SOAP fault.
 */
final class UnRegisterCardResponse extends AbstractResponse
{
    #[\Override]
    public function isSuccessful(): bool
    {
        return (bool) $this->data;
    }
}
