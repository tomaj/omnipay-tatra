<?php

namespace Omnipay\CardPay\Message;

use Omnipay\Common\Message\AbstractResponse;

class CompleteAuthorizeResponse extends AbstractResponse
{
    const SUCCESS = 'OK';

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

    public function getRc()
    {
        if (isset($this->data['RC'])) {
            return $this->data['RC'];
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

    public function getCid()
    {
        if (isset($this->data['CID'])) {
            return $this->data['CID'];
        }
        return null;
    }

    public function getTransactionReference()
    {
        if (isset($this->data['TID'])) {
            return $this->data['TID'];
        }

        return null;
    }
}
