<?php

namespace Omnipay\ComfortPay\Message;

use Money\Currency;
use Money\Currencies\ISOCurrencies;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Core\Sign\HmacSign;
use Omnipay\Core\Sign\Aes256Sign;
use Omnipay\Core\Message\AbstractRequest;

class CompletePurchaseRequest extends AbstractRequest
{
    public function getData()
    {
        $sharedSecret = $this->getParameter('sharedSecret');

        $vs = isset($_GET['VS']) ? $_GET['VS'] : '';
        $ac = isset($_GET['AC']) ? $_GET['AC'] : '';
        $cid = isset($_GET['CID']) ? $_GET['CID'] : '';
        $res = isset($_GET['RES']) ? $_GET['RES'] : '';
        $tres = isset($_GET['TRES']) ? $_GET['TRES'] : '';
        $tid = isset($_GET['TID']) ? $_GET['TID'] : '';
        $cc = isset($_GET['CC']) ? $_GET['CC'] : '';
        $rc = isset($_GET['RC']) ? $_GET['RC'] : '';
        $timestamp = isset($_GET['TIMESTAMP']) ? $_GET['TIMESTAMP'] : '';

        if ($vs != $this->getVs()) {
            throw new InvalidRequestException('Variable symbol mismatch');
        }

        if (strlen($sharedSecret) == 128) {
            $curr = (new ISOCurrencies())->numericCodeFor(new Currency($this->getCurrency()));
            $data = "{$this->getAmount()}{$curr}{$this->getVs()}{$res}{$ac}{$tres}{$cid}{$cc}{$rc}{$tid}{$timestamp}";
            $sign = new HmacSign();
            if ($sign->sign($data, $sharedSecret) != $_GET['HMAC']) {
                throw new InvalidRequestException('incorect signature');
            }
        } elseif (strlen($sharedSecret) == 64) {
            $data = "{$this->getVs()}{$tres}{$ac}{$cid}";
            $sign = new Aes256Sign();
            if ($sign->sign($data, $sharedSecret) != $_GET['SIGN']) {
                throw new InvalidRequestException('incorect signature');
            }
        } else {
            throw new \Exception('Unknown key length');
        }

        return [
            'RES' => $res,
            'VS' => $vs,
            'CC' => $cc,
            'CID' => $cid,
            'TRES' => $tres,
            'RC' => $rc,
        ];
    }
    
    public function sendData($data)
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
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
}
