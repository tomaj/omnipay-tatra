<?php

namespace Omnipay\TatraPay\Message;

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
        return $this;
    }

    public function getData()
    {
        $this->validate('sharedSecret', 'mid', 'vs', 'rurl');
        $data = [];
        $data['PT'] = 'TatraPay';
        $data['MID'] = $this->getMid();
        $data['CURR'] = (new ISOCurrencies())->numericCodeFor(new Currency($this->getCurrency()));
        $data['VS'] = $this->getVs();
        $data['CS'] = $this->getCs();
        $data['AMT'] = $this->getAmount();
        $data['DESC'] = $this->getDesc();
        $data['RSMS'] = $this->getRsms();
        $data['REM'] = $this->getRem();
        $data['AREDIR'] = $this->getAredir();
        $data['LANG'] = $this->getLang();
        $data['RURL'] = $this->getRurl();

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
        } elseif (strlen($sharedSecret) == 8) {
            $sign = new DesSign();
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
            $input = "{$this->getMid()}{$this->getAmount()}{$curr}{$this->getVs()}{$this->getSs()}{$this->getCs()}{$this->getRurl()}{$this->getRem()}{$this->getTimestamp()}";
            $data['HMAC'] = $this->generateSignature($input);
        } else {
            $input = "{$this->getMid()}{$this->getAmount()}{$curr}{$this->getVs()}{$this->getSs()}{$this->getCs()}{$this->getRurl()}";
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
                return $host . '/payment/tatrapay-hmac';
            } elseif (strlen($sharedSecret) == 64) {
                return $host . '/payment/tatrapay-aes256';
            } else {
                return $host . '/payment/tatrapay-des';
            }
        } else {
            if (strlen($sharedSecret) == 128) {
                return 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/tatrapay';
            } else {
                return 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp';
            }
        }
    }
}
