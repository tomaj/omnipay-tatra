<?php

declare(strict_types=1);

namespace Omnipay\CardPay\Message;

use Omnipay\Core\Message\AbstractRedirectResponse;

final class AuthorizeResponse extends AbstractRedirectResponse
{
    #[\Override]
    public function getRequest(): AuthorizeRequest
    {
        if (!$this->request instanceof AuthorizeRequest) {
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
