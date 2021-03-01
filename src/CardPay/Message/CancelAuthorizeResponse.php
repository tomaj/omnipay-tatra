<?php

namespace Omnipay\CardPay\Message;

use Omnipay\Common\Message\AbstractResponse;

class CancelAuthorizeResponse extends AbstractResponse
{
    const SUCCESS = 'OK';

    public function isSuccessful()
    {
        return $this->getRes() === self::SUCCESS;
    }

    public function getRes()
    {
        if (isset($this->data['RES'])) {
            return $this->data['RES'];
        }
        return null;
    }

    public function getErrorCode()
    {
        if (isset($this->data['errorCode'])) {
            return $this->data['errorCode'];
        }
        return null;
    }
}
