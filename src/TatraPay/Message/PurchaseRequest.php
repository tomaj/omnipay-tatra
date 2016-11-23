<?php

namespace Omnipay\TatraPay\Message;

use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Common\Currency;
use Omnipay\TatraPay\Sign\DesSign;
use Omnipay\TatraPay\Sign\HmacSign;
use Omnipay\TatraPay\Sign\Aes256Sign;


class PurchaseRequest extends AbstractRequest
{
    public function initialize(array $parameters = array())
    {
        parent::initialize($parameters);
        $this->setTimestamp(date('dmYHis'));
        return $this;
    }

    public function getMid()
    {
        return $this->getParameter('mid');
    }
    public function setMid($value)
    {
        return $this->setParameter('mid', $value);
    }

    public function getSharedSecret()
    {
        return $this->getParameter('sharedSecret');
    }
    public function setSharedSecret($value)
    {
        return $this->setParameter('sharedSecret', $value);
    }

    public function getAmt()
    {
        return $this->getParameter('amt');
    }
    public function setAmt($value)
    {
        return $this->setParameter('amt', $value);
    }

    public function getCurr()
    {
        return $this->getParameter('curr');
    }
    public function setCurr($value)
    {
        return $this->setParameter('curr', $value);
    }

    public function getVs()
    {
        return $this->getParameter('vs');
    }
    public function setVs($value)
    {
        return $this->setParameter('vs', $value);
    }

    public function getCs()
    {
        return $this->getParameter('cs');
    }
    public function setCs($value)
    {
        return $this->setParameter('cs', $value);
    }

    public function getSs()
    {
        return $this->getParameter('ss');
    }
    public function setSs($value)
    {
        return $this->setParameter('ss', $value);
    }

    public function getDesc()
    {
        return $this->getParameter('desc');
    }
    public function setDesc($value)
    {
        return $this->setParameter('desc', $value);
    }

    public function getRsms()
    {
        return $this->getParameter('rsms');
    }
    public function setRsms($value)
    {
        return $this->setParameter('rsms', $value);
    }

    public function getAredir()
    {
        return $this->getParameter('aredis');
    }
    public function setAredir($value)
    {
        return $this->setParameter('aredir', $value);
    }

    public function getLang()
    {
        return $this->getParameter('lang');
    }
    public function setLang($value)
    {
        return $this->setParameter('lang', $value);
    }

    public function getRem()
    {
        return $this->getParameter('rem');
    }
    public function setRem($value)
    {
        return $this->setParameter('rem', $value);
    }

    public function getTimestamp()
    {
        return $this->getParameter('timestamp');
    }
    public function setTimestamp($value)
    {
        return $this->setParameter('timestamp', $value);
    }

    public function getRurl()
    {
        return $this->getParameter('rurl');
    }
    public function setRurl($value)
    {
        return $this->setParameter('rurl', $value);
    }

    public function getData()
    {
        $this->validate('amt', 'sharedSecret', 'mid', 'curr', 'vs', 'rurl');
        $data = [];
        $data['PT'] = 'TatraPay';
        $data['MID'] = $this->getMid();
        $data['CURR'] = Currency::find($this->getCurrency())->getNumeric();
        $data['VS'] = $this->getVs();
        $data['CS'] = $this->getCs();
        $data['AMT'] = $this->getAmt();
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

        $curr = Currency::find($this->getCurrency())->getNumeric();;

        if (strlen($sharedSecret) == 128) {
            $input = "{$this->getMid()}{$this->getAmt()}{$curr}{$this->getVs()}{$this->getSs()}{$this->getCs()}{$this->getRurl()}{$this->getRem()}{$this->getTimestamp()}";
            $data['HMAC'] = $this->generateSignature($input);
        } else {
            $input = "{$this->getMid()}{$this->getAmt()}{$curr}{$this->getVs()}{$this->getSs()}{$this->getCs()}{$this->getRurl()}";
            $data['SIGN'] = $this->generateSignature($input);
        }
        
        return $this->response = new PurchaseResponse($this, $data);
    }

    public function getEndpoint()
    {
        $sharedSecret = $this->getParameter('sharedSecret');

        if ($this->getTestmode()) {
            if (strlen($sharedSecret) == 128) {
                return 'http://localhost:3333/payment/tatrapay-hmac';
            } elseif (strlen($sharedSecret) == 64) {
                return 'http://localhost:3333/payment/tatrapay-aes256';
            } else {
                return 'http://localhost:3333/payment/tatrapay-des';
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