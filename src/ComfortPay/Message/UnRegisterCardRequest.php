<?php

namespace Omnipay\ComfortPay\Message;

class UnRegisterCardRequest extends AbstractSoapRequest
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
            if ((int) $data['cardId'] % 2 === 0) {
                return $this->response = new UnRegisterCardResponse($this, 0);
            }
            return $this->response = new UnRegisterCardResponse($this, 5);
        }

        $request = new \stdClass();
        $request->idOfCard = $data['cardId'];

        $client = $this->getSoapClient();
        $response = $client->unRegisterCard($request);

        return $this->response = new UnRegisterCardResponse($this, $response);
    }
}
