<?php

namespace Omnipay\ComfortPay\Message;

use Omnipay\ComfortPay\Gateway;

class ChargeRequest extends AbstractSoapRequest
{
    public function getTransactionType()
    {
        return $this->getParameter('transactionType');
    }

    public function setTransactionType($value)
    {
        return $this->setParameter('transactionType', $value);
    }

    public function getWs()
    {
        return $this->getParameter('ws');
    }

    public function setWs($value)
    {
        return $this->setParameter('ws', $value);
    }

    public function getTerminalId()
    {
        return $this->getParameter('terminalId');
    }

    public function setTerminalId($value)
    {
        return $this->setParameter('terminalId', $value);
    }

    public function getParentTransactionId()
    {
        return $this->getParameter('parentTransactionId');
    }

    public function setParentTransactionId($value)
    {
        return $this->setParameter('parentTransactionId', $value);
    }

    public function getReferedCardId()
    {
        return $this->getParameter('referedCardId');
    }

    public function setReferedCardId($value)
    {
        return $this->setParameter('referedCardId', $value);
    }

    public function getE2eReference()
    {
        return $this->getParameter('e2eReference');
    }

    public function setE2eReference($value)
    {
        return $this->setParameter('e2eReference', $value);
    }

    public function getSubmerchantId()
    {
        return $this->getParameter('submerchantId');
    }

    public function setSubmerchantId($value)
    {
        return $this->setParameter('submerchantId', $value);
    }

    public function getLocation()
    {
        return $this->getParameter('location');
    }

    public function setLocation($value)
    {
        return $this->setParameter('location', $value);
    }

    public function getCity()
    {
        return $this->getParameter('city');
    }

    public function setCity($value)
    {
        return $this->setParameter('city', $value);
    }

    public function getAlpha2CountryCode()
    {
        return $this->getParameter('alpha2CountryCode');
    }

    public function setAlpha2CountryCode($value)
    {
        return $this->setParameter('alpha2CountryCode', $value);
    }

    public function getData()
    {
        $this->validate('terminalId', 'amount', 'transactionId', 'transactionType', 'referedCardId', 'ws', 'currency');

        if (in_array($this->getTransactionType(), [Gateway::TRANSACTION_TYPE_PREAUTH_CONFIRM, Gateway::TRANSACTION_TYPE_PREAUTH_CANCEL, Gateway::TRANSACTION_TYPE_CHARGEBACK])) {
            $this->validate('parentTransactionId');
        }

        $data = parent::getData();
        $data = array_merge($data, [
            'transactionType' => $this->getTransactionType(),
            'transactionId' => $this->getTransactionId(),
            'parentTransactionId' => $this->getParentTransactionId(),
            'referedCardId' => $this->getReferedCardId(),
            'merchantId' => $this->getWs(),
            'terminalId' => $this->getTerminalId(),
            'amount' => $this->getAmount(),
            'cc' => $this->getCurrency(),
            'vs' => $this->getVs(),
            'ss' => $this->getSs(),
            'e2eReference' => $this->getE2eReference(),
            'submerchantId' => $this->getSubmerchantId(),
            'location' => $this->getLocation(),
            'city' => $this->getCity(),
            'alpha2CountryCode' => $this->getAlpha2CountryCode(),
        ]);

        if (empty($data['e2eReference'])) {
            $this->validate('ss', 'vs');
        }

        if (empty($data['ss']) && empty($data['vs'])) {
            $this->validate('e2eReference');
        }

        if (!empty($data['submerchantId']) || !empty($data['location']) || !empty($data['city']) || !empty($data['alpha2CountryCode'])) {
            $this->validate('submerchantId', 'location', 'city', 'alpha2CountryCode');
        }

        return $data;
    }

    public function sendData($data)
    {
        if ($this->getTestmode()) {
            if ((int)$this->getReferedCardId() % 2 == 0) {
                return $this->response = new CardTransactionResponse(
                    $this,
                    ['transactionId' => $data['transactionId'], 'transactionStatus' => '02', 'transactionApproval' => '123']
                );
            }
            return $this->response = new CardTransactionResponse(
                $this,
                ['transactionId' => $data['transactionId'], 'transactionStatus' => '00', 'transactionApproval' => '123']
            );
        }

        $req = new \stdClass();
        $req->transactionId = $data['transactionId'];
        $req->referedCardId = $data['referedCardId'];
        $req->merchantId = $data['merchantId'];
        $req->terminalId = $data['terminalId'];
        $req->amount = $data['amount'];
        $req->parentTransactionId = $data['parentTransactionId'];
        $req->cc = $data['cc'];

        $transactionIdentificator = new \stdClass();

        if (!empty($data['vs']) && !empty($data['ss'])) {
            $symbols = new \stdClass();
            $symbols->variableSymbol = $data['vs'];
            $symbols->specificSymbol = $data['ss'];
            $transactionIdentificator->symbols = $symbols;
        } else {
            $transactionIdentificator->e2eReference = $data['e2eReference'];
        }

        $req->transactionIdentificator = $transactionIdentificator;

        if (!empty($data['submerchantId']) && !empty($data['location']) && !empty($data['city']) && !empty($data['alpha2CountryCode'])) {
            $ipspData = new \stdClass();
            $ipspData->submerchantId = $data['submerchantId'];
            $ipspData->location = $data['location'];
            $ipspData->city = $data['city'];
            $ipspData->alpha2CountryCode = $data['alpha2CountryCode'];
            $req->ipspData = $ipspData;
        }

        $request = new \stdClass();
        $request->req = $req;
        $request->transactionType = $data['transactionType'];

        $client = $this->getSoapClient();
        try {
            $response = $client->doCardTransaction($request);
        } catch (\SoapFault $sf) {
            // special case for TB :-(
            // they started to return an error when they are not able to charge this card. We don't want to treat it as SoapFault Exception because it is a "regular" answer. SoapFault is for error on network, outage, etc...
            // here is an error example:
            // <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope" xmlns:SOAP-ENC="http://www.w3.org/2003/05/soap-encoding" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:types="urn:tatrabanka:ibanking:Types" xmlns:teleplatba="urn:tatrabanka:ibanking:Teleplatba" xmlns:vposCommon="http://www.ri-rpc.sk/cmsdd/aim/common" xmlns:vposAuth="http://www.ri-rpc.sk/cmsdd/aim/vposAuth" xmlns:vposAuthFollow="http://www.ri-rpc.sk/cmsdd/aim/vposAuthFollow" xmlns:vposAuthResponse="http://www.ri-rpc.sk/cmsdd/aim/vposAuthResponse"><SOAP-ENV:Body><SOAP-ENV:Fault><SOAP-ENV:Code><SOAP-ENV:Value>SOAP-ENV:Receiver</SOAP-ENV:Value></SOAP-ENV:Code><SOAP-ENV:Reason><SOAP-ENV:Text xml:lang="en"></SOAP-ENV:Text></SOAP-ENV:Reason><SOAP-ENV:Detail><types:ExceptionType><method>doCardTransaction</method><file>ImplFile</file><line>1359</line><errorCode>50051</errorCode><subsystemId>19</subsystemId><subsystemErrorCode>0</subsystemErrorCode><message></message></types:ExceptionType></SOAP-ENV:Detail></SOAP-ENV:Fault></SOAP-ENV:Body></SOAP-ENV:Envelope>
            if ($sf->faultcode == 'SOAP-ENV:Receiver') {
                $lastResponse = $client->__getLastResponse();
                $xml = new \SimpleXMLElement($lastResponse);
                $path = $xml->xpath('//errorCode');
                if ($path && isset($path[0])) {
                    $errorCode = (int)$path;
                    // More info about error codes: https://github.com/tomaj/omnipay-tatra/issues/12
                    if ($errorCode !== 50000) {
                        return $this->response = new CardTransactionResponse($this, [
                            'transactionId' => false,
                            'transactionStatus' => false,
                            'transactionApproval' => false,
                        ]);
                    }
                }
            }
            throw $sf;
        }

        return $this->response = new CardTransactionResponse($this, [
            'transactionId' => $response->res->transactionId,
            'transactionStatus' => $response->res->transactionStatus,
            'transactionApproval' => $response->res->transactionStatus,
        ]);
    }
}
