<?php

namespace Omnipay\ComfortPay\Message;

use SoapClient;

abstract class AbstractSoapRequest extends \Omnipay\Core\Message\AbstractRequest
{

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
        $this->validate('certPath', 'certPass');

        return [
            'certPath' => $this->getParameter('certPath'),
            'certPass' => $this->getParameter('certPass'),
        ];
    }

    protected function getSoapClient()
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
