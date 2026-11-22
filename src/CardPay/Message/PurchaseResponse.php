<?php

declare(strict_types=1);

namespace Omnipay\CardPay\Message;

use Omnipay\Core\Message\AbstractRedirectResponse;

final class PurchaseResponse extends AbstractRedirectResponse
{
    #[\Override]
    public function getRequest(): PurchaseRequest
    {
        if (!$this->request instanceof PurchaseRequest) {
            throw new \LogicException('Response was created for an unexpected request ' . $this->request::class);
        }

        return $this->request;
    }

    #[\Override]
    protected function getEndpoint(): string
    {
        return $this->getRequest()->getEndpoint();
    }
}
