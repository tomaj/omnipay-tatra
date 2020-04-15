<?php

namespace Omnipay\ComfortPay\Message;

use Omnipay\ComfortPay\Gateway;
use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\ResponseInterface;

class CheckCardResponse extends AbstractResponse implements ResponseInterface
{
    public function isSuccessful()
    {
        return in_array($this->data->status, [
            Gateway::CARD_STATUS_OK,
            Gateway::CARD_STATUS_FAIL,
            Gateway::CARD_STATUS_UNKNOWN
        ]);
    }
}
