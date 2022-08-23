<?php

namespace Omnipay\Core\Message;

abstract class AbstractRequest extends \Omnipay\Common\Message\AbstractRequest
{
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

    public function getVs()
    {
        return $this->getParameter('vs');
    }

    public function setVs($value)
    {
        return $this->setParameter('vs', $value);
    }

    public function getCs()
    {
        return $this->getParameter('cs');
    }

    public function setCs($value)
    {
        return $this->setParameter('cs', $value);
    }

    public function getSs()
    {
        return $this->getParameter('ss');
    }

    public function setSs($value)
    {
        return $this->setParameter('ss', $value);
    }

    public function getDesc()
    {
        return $this->getParameter('desc');
    }

    public function setDesc($value)
    {
        return $this->setParameter('desc', $value);
    }

    public function getRsms()
    {
        return $this->getParameter('rsms');
    }

    public function setRsms($value)
    {
        return $this->setParameter('rsms', $value);
    }

    public function getAredir()
    {
        return $this->getParameter('aredir') !== null ? (int)(boolean)$this->getParameter('aredir') : null;
    }

    public function setAredir($value)
    {
        return $this->setParameter('aredir', $value);
    }

    public function getLang()
    {
        return $this->getParameter('lang');
    }

    public function setLang($value)
    {
        return $this->setParameter('lang', $value);
    }

    public function getRem()
    {
        return $this->getParameter('rem');
    }

    public function setRem($value)
    {
        return $this->setParameter('rem', $value);
    }

    public function getTimestamp()
    {
        return $this->getParameter('timestamp');
    }

    public function setTimestamp($value)
    {
        return $this->setParameter('timestamp', $value);
    }

    public function getRurl()
    {
        return $this->getParameter('rurl');
    }

    public function setRurl($value)
    {
        return $this->setParameter('rurl', $value);
    }
}
