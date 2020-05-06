<?php

namespace Omnipay\ComfortPay\Message;

use Omnipay\ComfortPay\Gateway;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\ResponseInterface;

class CheckCardResponse extends AbstractResponse implements ResponseInterface
{
    public function isSuccessful()
    {
        return $this->data->status == Gateway::CARD_STATUS_OK;
    }

    public function getStatus()
    {
        return $this->data->status;
    }
}
