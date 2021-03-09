<?php

namespace Omnipay\CardPay;

use Omnipay\Common\AbstractGateway;

class Gateway extends AbstractGateway
{
    public function getName()
    {
        return 'CardPay Gateway';
    }

    public function getDefaultParameters()
    {
        return [
            'mid' => '',
            'sharedSecret' => '',
        ];
    }

    public function getMid()
    {
        return $this->getParameter('mid');
    }

    public function setMid($value)
    {
        return $this->setParameter('mid', $value);
    }

    public function getSharedSecret()
    {
        return $this->getParameter('sharedSecret');
    }

    public function setSharedSecret($value)
    {
        return $this->setParameter('sharedSecret', $value);
    }

    public function purchase(array $parameters = array())
    {
        return $this->createRequest(\Omnipay\CardPay\Message\PurchaseRequest::class, $parameters);
    }

    public function completePurchase(array $parameters = array())
    {
        return $this->createRequest(\Omnipay\CardPay\Message\CompletePurchaseRequest::class, $parameters);
    }

    public function authorize(array $parameters = array())
    {
        return $this->createRequest(\Omnipay\CardPay\Message\AuthorizeRequest::class, $parameters);
    }

    public function completeAuthorize(array $parameters = array())
    {
        return $this->createRequest(\Omnipay\CardPay\Message\CompleteAuthorizeRequest::class, $parameters);
    }

    public function cancelAuthorize(array $parameters = array())
    {
        return $this->createRequest(\Omnipay\CardPay\Message\CancelAuthorizeRequest::class, $parameters);
    }
}
