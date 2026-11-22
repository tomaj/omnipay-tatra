<?php

namespace Omnipay\CardPay\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

class AuthorizeResponse extends AbstractResponse implements RedirectResponseInterface
{
    public function isSuccessful()
    {
        return false;
    }

    public function isRedirect()
    {
        return true;
    }

    public function getRedirectUrl()
    {
        return $this->getRequest()->getEndpoint() . '?' . http_build_query($this->getRedirectData());
    }

    /**
     * @return AuthorizeRequest
     */
    public function getRequest()
    {
        if (!$this->request instanceof AuthorizeRequest) {
            throw new \LogicException('Response was created for an unexpected request ' . get_class($this->request));
        }

        return $this->request;
    }

    public function getRedirectMethod()
    {
        return 'GET';
    }

    public function getRedirectData()
    {
        return $this->data;
    }
}
