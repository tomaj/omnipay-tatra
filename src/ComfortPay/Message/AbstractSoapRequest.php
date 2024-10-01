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

    public function getProxyHost()
    {
        return $this->getParameter('proxyHost');
    }

    public function setProxyHost($value)
    {
        return $this->setParameter('proxyHost', $value);
    }

    public function getProxyPort()
    {
        return $this->getParameter('proxyPort');
    }

    public function setProxyPort($value)
    {
        return $this->setParameter('proxyPort', $value);
    }

    public function getProxyLogin()
    {
        return $this->getParameter('proxyLogin');
    }

    public function setProxyLogin($value)
    {
        return $this->setParameter('proxyLogin', $value);
    }

    public function getProxyPass()
    {
        return $this->getParameter('proxyPass');
    }

    public function setProxyPass($value)
    {
        return $this->setParameter('proxyPass', $value);
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
            'proxy_host' => $this->getParameter('proxyHost'),
            'proxy_port' => $this->getParameter('proxyPort'),
            'proxy_login' => $this->getParameter('proxyLogin'),
            'proxy_password' => $this->getParameter('proxyPassword'),
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                ]
            ])
        ];
        return new SoapClient(__DIR__ . '/../Teleplatba_1_0.wsdl', $options);
    }
}
