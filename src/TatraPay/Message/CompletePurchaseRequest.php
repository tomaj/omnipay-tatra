<?php

namespace Omnipay\TatraPay\Message;

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

        if (strlen($sharedSecret) == 128) {
            $curr = (new ISOCurrencies())->numericCodeFor(new Currency($this->getCurrency()));
            $tid = $_GET['TID'] ?? '';
            $data = "{$this->getAmount()}{$curr}{$this->getVs()}{$this->getSs()}{$this->getCs()}{$_GET['RES']}{$tid}{$_GET['TIMESTAMP']}";
            $sign = new HmacSign();
            if (!Signature::equals($sign->sign($data, $sharedSecret), $_GET['HMAC'] ?? null)) {
                throw new InvalidRequestException('incorect signature');
            }
        } elseif (strlen($sharedSecret) == 64) {
            $data = "{$_GET['VS']}{$_GET['RES']}";
            $sign = new Aes256Sign();
            if (!Signature::equals($sign->sign($data, $sharedSecret), $_GET['SIGN'] ?? null)) {
                throw new InvalidRequestException('incorect signature');
            }
        } elseif (strlen($sharedSecret) == 8) {
            $data = "{$_GET['VS']}{$_GET['RES']}";
            $sign = new DesSign();
            if (!Signature::equals($sign->sign($data, $sharedSecret), $_GET['SIGN'] ?? null)) {
                throw new InvalidRequestException('incorect signature');
            }
        } else {
            throw new \Exception('Unknown key length');
        }

        return [
            'RES' => $_GET['RES'],
            'VS' => $_GET['VS'],
        ];
    }
    public function sendData($data)
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
    }
}
