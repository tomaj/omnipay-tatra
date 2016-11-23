<?php

namespace Omnipay\TatraPay;

use Omnipay\Common\AbstractGateway;

class Gateway extends AbstractGateway
{
    public function getName()
    {
        return 'TatraPay Gateway';
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
        if (isset($parameters['amount'])) {
            $parameters['amt'] = $parameters['amount'];
        }
        if (isset($parameters['currency'])) {
            $parameters['curr'] = $parameters['currency'];
        }
        
        return $this->createRequest('\Omnipay\TatraPay\Message\PurchaseRequest', $parameters);
    }
}