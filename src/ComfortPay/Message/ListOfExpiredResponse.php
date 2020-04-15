<?php

namespace Omnipay\ComfortPay\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\ResponseInterface;

class ListOfExpiredResponse extends AbstractResponse implements ResponseInterface
{
    public function isSuccessful()
    {
        return count($this->data) > 0;
    }

    public function getExpires()
    {
        return $this->data;
    }
}
