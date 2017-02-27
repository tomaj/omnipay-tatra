<?php

namespace Omnipay\ComfortPay\Message;

abstract class AbstractSoapRequest extends \Omnipay\Core\Message\AbstractRequest
{
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
}
