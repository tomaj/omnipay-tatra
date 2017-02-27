<?php

namespace Omnipay\ComfortPay\Message;

use SoapClient;

class CheckCardRequest extends AbstractSoapRequest
{
    public function getCardId()
    {
        return $this->getParameter('cardId');
    }

    public function setCardId($value)
    {
        return $this->setParameter('cardId', $value);
    }

    public function getData()
    {
        $this->validate('terminalId', 'ws', 'cardId', 'certPath', 'certPass');

        return [
            'terminalId' => $this->getParameter('terminalId'),
            'ws' => $this->getParameter('ws'),
            'cardId' => $this->getParameter('cardId'),
            'certPath' => $this->getParameter('certPath'),
            'certPass' => $this->getParameter('certPass'),
        ];
    }

    public function sendData($data)
    {
        if ($this->getTestmode()) {
            if (intval($data['cardId']) % 2 == 0) {
                return $this->response = new CheckCardResponse($this, 0);
            }
            return $this->response = new CheckCardResponse($this, 5);
        }

        $options = [
            'trace' => true,
            'exception' => true,
            'local_cert' => $this->getParameter('certPath'),
            'passphrase' => $this->getParameter('certPass'),
            'connection_timeout' => 5,
            'keep_alive' => false,
            'soap_version' => SOAP_1_2,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                ]
            ])
        ];
        $this->client = new SoapClient(__DIR__ . '/../comfortpay.wsdl', $options);

        $response = $this->client->checkCard($data['cardId']);

		return $this->response = new CheckCardResponse($this, $response);
    }
}
