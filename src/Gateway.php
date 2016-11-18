<?php

namespace Omnipay\Tatra;

use Omnipay\Common\AbstractGateway;

class Gateway extends AbstractGateway
{
    public function getName()
    {
        return 'TatraPay AES Gateway';
    }

    public function getDefaultParameters()
    {
        return array(
            'mid' => '',
            'sharedSecret' => '',
        );
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

        return $this->createRequest('\Omnipay\Tatra\Message\PurchaseRequest', $parameters);
    }

    // public function completePurchase(array $parameters = array())
    // {
    //     return $this->createRequest('\Omnipay\Buckaroo\Message\CompletePurchaseRequest', $parameters);
    // }
}