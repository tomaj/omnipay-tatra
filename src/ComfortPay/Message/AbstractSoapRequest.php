<?php

namespace Omnipay\ComfortPay\Message;

abstract class AbstractSoapRequest extends \Omnipay\Core\Message\AbstractRequest
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

    public function getCertPath()
    {
        return $this->getParameter('certPath');
    }

    public function setCertPath($value)
    {
        return $this->setParameter('certPath', $value);
    }

    public function getCertPass()
    {
        return $this->getParameter('certPass');
    }

    public function setCertPass($value)
    {
        return $this->setParameter('certPass', $value);
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

    private function getSoapClient()
    {
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
        return new SoapClient(__DIR__ . '/../comfortpay.wsdl', $options);
    }
}
