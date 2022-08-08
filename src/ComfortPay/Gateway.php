<?php

namespace Omnipay\ComfortPay;

use Omnipay\ComfortPay\Message\ListOfExpiredRequest;
use Omnipay\ComfortPay\Message\TransactionStatusRequest;
use Omnipay\Common\AbstractGateway;

class Gateway extends AbstractGateway
{
    const TRANSACTION_TYPE_PURCHASE = 'PURCHASE';
    const TRANSACTION_TYPE_PREAUTHORIZATION = 'PREAUTHORIZATION';
    const TRANSACTION_TYPE_PREAUTH_CONFIRM = 'PREAUTH-CONFIRM';
    const TRANSACTION_TYPE_PREAUTH_CANCEL = 'PREAUTH-CANCEL';
    const TRANSACTION_TYPE_CHARGEBACK = 'CHARGEBACK';

    const CARD_STATUS_OK = 'OK';
    const CARD_STATUS_FAIL = 'FAIL';
    const CARD_STATUS_UNKNOWN = 'UNKNOWN';

    public function getName()
    {
        return 'ComfortPay Gateway';
    }

    public function getDefaultParameters()
    {
        return [
            'mid' => '',
            'sharedSecret' => '',
            'testHost' => '',
            'terminalId' => '',
            'ws' => '',
            'certPath' => '',
            'certPass' => '',
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

    public function getTestHost()
    {
        return $this->getParameter('testHost');
    }

    public function setTestHost($value)
    {
        return $this->setParameter('testHost', $value);
    }

    public function getTerminalId()
    {
        return $this->getParameter('terminalId');
    }

    public function setTerminalId($value)
    {
        return $this->setParameter('terminalId', $value);
    }

    public function getWs()
    {
        return $this->getParameter('ws');
    }

    public function setWs($value)
    {
        return $this->setParameter('ws', $value);
    }

    public function getCertPath()
    {
        return $this->getParameter('certPath');
    }

    public function setCertPath($value)
    {
        return $this->setParameter('certPath', $value);
    }

    public function getCertPass()
    {
        return $this->getParameter('certPass');
    }

    public function setCertPass($value)
    {
        return $this->setParameter('certPass', $value);
    }

    public function purchase(array $parameters = array())
    {
        return $this->createRequest(\Omnipay\ComfortPay\Message\PurchaseRequest::class, $parameters);
    }

    public function completePurchase(array $parameters = array())
    {
        return $this->createRequest(\Omnipay\ComfortPay\Message\CompletePurchaseRequest::class, $parameters);
    }

    public function charge(array $parameters = array())
    {
        return $this->createRequest(\Omnipay\ComfortPay\Message\ChargeRequest::class, $parameters);
    }

    public function checkCard(array $parameters = array())
    {
        return $this->createRequest(\Omnipay\ComfortPay\Message\CheckCardRequest::class, $parameters);
    }

    public function listOfExpirePerId(array $parameters = array())
    {
        return $this->createRequest(\Omnipay\ComfortPay\Message\ListOfExpirePerIdRequest::class, $parameters);
    }

    public function listOfExpired(array $parameters = array())
    {
        return $this->createRequest(\Omnipay\ComfortPay\Message\ListOfExpiredRequest::class, $parameters);
    }

    public function checkTransaction(array $parameters = array())
    {
        return $this->createRequest(\Omnipay\ComfortPay\Message\TransactionStatusRequest::class, $parameters);
    }
}
