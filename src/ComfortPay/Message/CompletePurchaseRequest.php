<?php

namespace Omnipay\ComfortPay\Message;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Core\Message\AbstractRequest;
use Omnipay\Core\Sign\Aes256Sign;
use Omnipay\Core\Sign\HmacSign;
use Omnipay\Core\Sign\Signature;

class CompletePurchaseRequest extends AbstractRequest
{
    public function getData()
    {
        $sharedSecret = $this->getParameter('sharedSecret');

        $vs = $_GET['VS'] ?? '';
        $ac = $_GET['AC'] ?? '';
        $cid = $_GET['CID'] ?? '';
        $res = $_GET['RES'] ?? '';
        $tres = $_GET['TRES'] ?? '';
        $tid = $_GET['TID'] ?? '';
        $cc = $_GET['CC'] ?? '';
        $rc = $_GET['RC'] ?? '';
        $timestamp = $_GET['TIMESTAMP'] ?? '';

        if ($vs != $this->getVs()) {
            throw new InvalidRequestException('Variable symbol mismatch');
        }

        if (strlen($sharedSecret) == 128) {
            $curr = (new ISOCurrencies())->numericCodeFor(new Currency($this->getCurrency()));
            $data = "{$this->getAmount()}{$curr}{$this->getVs()}{$res}{$ac}{$tres}{$cid}{$cc}{$rc}{$tid}{$timestamp}";
            $sign = new HmacSign();
            if (!Signature::equals($sign->sign($data, $sharedSecret), $_GET['HMAC'] ?? null)) {
                throw new InvalidRequestException('incorect signature');
            }
        } elseif (strlen($sharedSecret) == 64) {
            $data = "{$this->getVs()}{$tres}{$ac}{$cid}";
            $sign = new Aes256Sign();
            if (!Signature::equals($sign->sign($data, $sharedSecret), $_GET['SIGN'] ?? null)) {
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
