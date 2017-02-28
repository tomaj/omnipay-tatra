<?php

namespace Omnipay\ComfortPay\Message;

use Omnipay\Common\Currency;
use SoapParam;

class ChargeRequest extends AbstractSoapRequest
{
    public function getTerminalId()
    {
        return $this->getParameter('terminalId');
    }

    public function setTerminalId($value)
    {
        return $this->setParameter('terminalId', $value);
    }

    public function getWs()
    {
        return $this->getParameter('ws');
    }

    public function setWs($value)
    {
        return $this->setParameter('ws', $value);
    }

	public function getCid()
    {
        return $this->getParameter('cid');
    }

    public function setCid($value)
    {
        return $this->setParameter('cid', $value);
    }

	public function getTransactionId()
    {
        return $this->getParameter('transactionId');
    }

    public function setTransactionId($value)
    {
        return $this->setParameter('transactionId', $value);
    }    

    public function getData()
    {
        $this->validate('terminalId', 'ws', 'amount', 'currency', 'transactionId', 'cid');

        $data = parent::getData();
        $data = array_merge($data, [
        	'terminalId' => $this->getTerminalId(),
            'ws' => $this->getWs(),
            'cid' => $this->getCid(),
            'transactionId' => $this->getTransactionId(),
            'amount' => $this->getAmount(),
            'currency' => Currency::find($this->getCurrency())->getNumeric(),
            'vs' => $this->getVs(),
            'ss' => $this->getSs(),
        ]);
        
        return $data;
    }

    public function sendData($data)
    {
        if ($this->getTestmode()) {
			if (intval($data['cid']) % 2 == 0) {
                return $this->response = new ChargeResponse($this, ['transactionStatus' => '02', 'transactionApproval' => '123']);
            }
            return $this->response = new ChargeResponse($this, ['transactionStatus' => '00', 'transactionApproval' => '123']);
        }

        $client = $this->getSoapClient();

		$data = [
            'transactionId' => $data['transactionId'],
            'referedCardId' => $data['cid'],
            'merchantId' => $data['ws'],
            'terminalId' => $data['terminalId'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'vs' => $data['vs'],
            'ss' => $data['ss'],
        ];
        $param = new SoapParam($data, 'TransactionRequest');
        $response = $client->doCardTransaction($param);

        return $this->response = new ChargeResponse($this, ['transactionStatus' => $response->transactionStatus, 'transactionApproval' => $response->transactionStatus]);
    }
}
