<?php

namespace Omnipay\ComfortPay\Message;

use Money\Currency;
use Money\Currencies\ISOCurrencies;
use Omnipay\Core\Sign\DesSign;
use Omnipay\Core\Sign\HmacSign;
use Omnipay\Core\Sign\Aes256Sign;
use Omnipay\Core\Message\AbstractRequest;

class PurchaseRequest extends AbstractRequest
{
    public function initialize(array $parameters = array())
    {
        parent::initialize($parameters);
        $this->setTimestamp(gmdate('dmYHis'));
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $this->setIpc($_SERVER['REMOTE_ADDR']);
        }
        $this->setAredir(1);

        return $this;
    }

    public function getIpc()
    {
        return $this->getParameter('ipc');
    }

    public function setIpc($value)
    {
        return $this->setParameter('ipc', $value);
    }

    public function getName()
    {
        return $this->getParameter('name');
    }

    public function setName($value)
    {
        return $this->setParameter('name', $value);
    }

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

    public function getTpay()
    {
        return 'Y';
    }



    public function getData()
    {
    
        $this->validate('sharedSecret', 'mid', 'vs', 'rurl');
        $data = [];
        $data['PT'] = 'CardPay';
        $data['MID'] = $this->getMid();
        $data['CURR'] = (new ISOCurrencies())->numericCodeFor(new Currency($this->getCurrency()));
        $data['VS'] = $this->getVs();
        $data['CS'] = $this->getCs();
        $data['AMT'] = $this->getAmount();
        $data['DESC'] = $this->getDesc();
        $data['RSMS'] = $this->getRsms();
        $data['AREDIR'] = $this->getAredir();
        $data['LANG'] = $this->getLang();
        $data['RURL'] = $this->getRurl();
        $data['IPC'] = $this->getIpc();
        $data['NAME'] = $this->getName();
        $data['TPAY'] = $this->getTpay();
        if ($this->getRem()) {
            $data['REM'] = $this->getRem();
        }
        $data['TEM'] = 1;

        $sharedSecret = $this->getParameter('sharedSecret');
        if (strlen($sharedSecret) == 128) {
            $data['TIMESTAMP'] = $this->getTimestamp();
        }
        return $data;
    }

    public function generateSignature($data)
    {
        $sharedSecret = $this->getParameter('sharedSecret');

        if (strlen($sharedSecret) == 128) {
            $sign = new HmacSign();
            return $sign->sign($data, $sharedSecret);
        } elseif (strlen($sharedSecret) == 64) {
            $sign = new Aes256Sign();
            return $sign->sign($data, $sharedSecret);
        } else {
            throw new \Exception('Unknown key length');
        }

        return $sign;
    }

    public function sendData($data)
    {
        $sharedSecret = $this->getParameter('sharedSecret');

        $curr = (new ISOCurrencies())->numericCodeFor(new Currency($this->getCurrency()));

        if (strlen($sharedSecret) == 128) {
            $input = "{$this->getMid()}{$this->getAmount()}{$curr}{$this->getVs()}{$this->getRurl()}{$this->getIpc()}{$this->getName()}{$this->getRem()}{$this->getTpay()}{$this->getTimestamp()}";
            $data['HMAC'] = $this->generateSignature($input);
        } else {
            $input = "{$this->getMid()}{$this->getAmount()}{$curr}{$this->getVs()}{$this->getCs()}{$this->getRurl()}{$this->getIpc()}{$this->getName()}{$this->getTpay()}";
            $data['SIGN'] = $this->generateSignature($input);
        }
        
        return $this->response = new PurchaseResponse($this, $data);
    }

    public function getEndpoint()
    {
        $sharedSecret = $this->getParameter('sharedSecret');

        if ($this->getTestmode()) {
            $host = $this->getParameter('testHost') ?: 'https://platby.tomaj.sk';

            if (strlen($sharedSecret) == 128) {
                return $host . '/payment/comfortpay-hmac';
            } else {
                return $host . '/payment/comfortpay-aes256';
            }
        } else {
            if (strlen($sharedSecret) == 128) {
                return 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/cardpay';
            } else {
                return 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp';
            }
        }
    }

    private function getCurrencyNumber($code)
    {
        $isoCurrencies = new ISOCurrencies();
        return $isoCurrencies->numericCodeFor(new Currency($code));
    }
}
