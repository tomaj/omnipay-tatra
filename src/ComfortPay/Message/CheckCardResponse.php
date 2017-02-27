<?php

namespace Omnipay\ComfortPay\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\ResponseInterface;

class CheckCardResponse extends AbstractResponse implements ResponseInterface
{
    public function isSuccessful()
    {
    	return $this->data == 0 || $this->data == 3;
    }
}
