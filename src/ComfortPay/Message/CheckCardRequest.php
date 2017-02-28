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
        $this->validate('cardId');

        $data = parent::getData();
        $data['cardId'] = $this->getCardId();
        return $data;
    }

    public function sendData($data)
    {
        if ($this->getTestmode()) {
            if (intval($data['cardId']) % 2 == 0) {
                return $this->response = new CheckCardResponse($this, 0);
            }
            return $this->response = new CheckCardResponse($this, 5);
        }

        $client = $this->getSoapClient();
        $response = $client->checkCard($data['cardId']);

        return $this->response = new CheckCardResponse($this, $response);
    }
}
