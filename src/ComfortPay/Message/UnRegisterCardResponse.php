<?php

namespace Omnipay\ComfortPay\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\ResponseInterface;

class UnRegisterCardResponse extends AbstractResponse implements ResponseInterface
{
    public function isSuccessful()
    {
        return (bool) $this->data;
    }
}
