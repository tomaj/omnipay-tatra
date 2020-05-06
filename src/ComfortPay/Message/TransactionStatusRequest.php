<?php

namespace Omnipay\ComfortPay\Message;

use Money\Currency;
use Money\Currencies\ISOCurrencies;

class TransactionStatusRequest extends AbstractSoapRequest
{
    public function getData()
    {
        $this->validate('transactionId');

        $data = parent::getData();
        $data = array_merge($data, [
            'transactionId' => $this->getTransactionId(),
        ]);

        return $data;
    }

    public function sendData($data)
    {
        if ($this->getTestmode()) {
            if (intval($data['cid']) % 2 == 0) {
                return $this->response = new ChargeResponse(
                    $this,
                    ['transactionId' => $data['transactionId'], 'transactionStatus' => '02', 'transactionApproval' => '123']
                );
            }
            return $this->response = new ChargeResponse(
                $this,
                ['transactionId' => $data['transactionId'], 'transactionStatus' => '00', 'transactionApproval' => '123']
            );
        }

        $request = new \stdClass();
        $request->transactionId = $data['transactionId'];

        $client = $this->getSoapClient();
        $response = $client->getTransactionStatus($request);

        return $this->response = new CardTransactionResponse($this, [
            'transactionId' => $response->res->transactionId,
            'transactionStatus' => $response->res->transactionStatus,
            'transactionApproval' => $response->res->transactionStatus
        ]);
    }
}
