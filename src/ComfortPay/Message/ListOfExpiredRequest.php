<?php

namespace Omnipay\ComfortPay\Message;

use SoapClient;
use SimpleXMLElement;

class ListOfExpiredRequest extends AbstractSoapRequest
{
    public function getExpDate()
    {
        return $this->getParameter('expDate');
    }

    public function setExpDate($value)
    {
        return $this->setParameter('expDate', $value);
    }

    public function getData()
    {
        $this->validate('expDate');

        $data = parent::getData();
        $data['expDate'] = $this->getExpDate();
        return $data;
    }

    public function sendData($data)
    {
        if ($this->getTestmode()) {
            return $this->response = new ListOfExpiredResponse($this, [
                ['id' => 12345],
                ['id' => 12346]
            ]);
        }

        $request = new \stdClass();
        $request->expDate = $data['expDate'];

        $client = $this->getSoapClient();
        $client->getListOfExpired($request);

        $xmlResponse = $client->__getLastResponse();
        $xml = new SimpleXMLElement($xmlResponse);

        $pairs = $xml->xpath('//list');

        $resultData = array_map(function ($element) {
            return [
                'id' => (string)$element,
            ];
        }, $pairs);

        return $this->response = new ListOfExpiredResponse($this, $resultData);
    }
}
