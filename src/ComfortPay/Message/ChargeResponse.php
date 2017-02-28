<?php

namespace Omnipay\ComfortPay\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\ResponseInterface;

class ChargeResponse extends AbstractResponse implements ResponseInterface
{
    public function isSuccessful()
    {
        return $this->getTransactionStatus() == '00';
    }

    public function getTransactionStatus()
    {
        if (!isset($this->data['transactionStatus'])) {
            return false;
    	}
    	return $this->data['transactionStatus'];
    }

    public function getTransactionApproval()
    {
        if (!isset($this->data['transactionApproval'])) {
            return false;
        }
    	return $this->data['transactionApproval'];
    }
}
