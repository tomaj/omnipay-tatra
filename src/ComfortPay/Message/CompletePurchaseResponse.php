<?php

namespace Omnipay\ComfortPay\Message;

use Omnipay\Common\Message\AbstractResponse;

class CompletePurchaseResponse extends AbstractResponse
{
    public const SUCCESS = 'OK';

    public function isSuccessful()
    {
        return static::SUCCESS === $this->getRes();
    }

    public function getRes()
    {
        if (isset($this->data['RES'])) {
            return $this->data['RES'];
        }
        return null;
    }

    public function getVs()
    {
        if (isset($this->data['VS'])) {
            return $this->data['VS'];
        }
        return null;
    }

    public function getCid()
    {
        if (isset($this->data['CID'])) {
            return $this->data['CID'];
        }
        return null;
    }

    public function getCc()
    {
        if (isset($this->data['CC'])) {
            return $this->data['CC'];
        }
        return null;
    }

    public function getTres()
    {
        if (isset($this->data['TRES'])) {
            return $this->data['TRES'];
        }
        return null;
    }

    public function getRc()
    {
        if (isset($this->data['RC'])) {
            return $this->data['RC'];
        }
        return null;
    }
}
