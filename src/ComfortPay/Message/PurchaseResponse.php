<?php

namespace Omnipay\ComfortPay\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Core\Message\AbstractRequest;

class PurchaseResponse extends AbstractResponse implements RedirectResponseInterface
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
        // todo - validated with '?'
        return $this->getRequest()->getEndpoint() . '?' . http_build_query($this->getRedirectData());
    }

    /**
     * @return PurchaseRequest
     */
    public function getRequest()
    {
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
