<?php

namespace Omnipay\CardPay\Message;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Core\Message\AbstractRequest;
use Omnipay\Core\Sign\Aes256Sign;
use Omnipay\Core\Sign\DesSign;
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
            $data = "{$this->getAmount()}{$curr}{$this->getVs()}{$res}{$ac}{$tres}{$cc}{$rc}{$tid}{$timestamp}";
            $sign = new HmacSign();
            $received = $_GET['HMAC'] ?? null;
            if (!Signature::equals($sign->sign($data, $sharedSecret), $received)) {
                $received = is_string($received) ? $received : '';
                throw new InvalidRequestException("incorrect signature for data: '{$data}', sign: '{$received}'");
            }
        } elseif (strlen($sharedSecret) == 64) {
            $data = "{$this->getVs()}{$res}{$ac}";
            $sign = new Aes256Sign();
            if (!Signature::equals($sign->sign($data, $sharedSecret), $_GET['SIGN'] ?? null)) {
                throw new InvalidRequestException('incorect signature');
            }
        } elseif (strlen($sharedSecret) == 8) {
            $data = "{$this->getVs()}{$res}{$ac}";
            $sign = new DesSign();
            if (!Signature::equals($sign->sign($data, $sharedSecret), $_GET['SIGN'] ?? null)) {
                throw new InvalidRequestException('incorect signature');
            }
        } else {
            throw new \Exception('Unknown key length');
        }

        return [
            'RES' => $res,
            'VS' => $vs,
            'RC' => $rc,
        ];
    }
    public function sendData($data)
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
    }
}
