<?php

namespace Omnipay\TatraPay\Message;

use Money\Currency;
use Money\Currencies\ISOCurrencies;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Core\Sign\DesSign;
use Omnipay\Core\Sign\HmacSign;
use Omnipay\Core\Sign\Aes256Sign;
use Omnipay\Core\Message\AbstractRequest;

class CompletePurchaseRequest extends AbstractRequest
{
    public function getData()
    {
        $sharedSecret = $this->getParameter('sharedSecret');

        if (strlen($sharedSecret) == 128) {
            $curr = (new ISOCurrencies())->numericCodeFor(new Currency($this->getCurrency()));
            $tid = isset($_GET['TID']) ? $_GET['TID'] : '';
            $data = "{$this->getAmount()}{$curr}{$this->getVs()}{$this->getSs()}{$this->getCs()}{$_GET['RES']}{$tid}{$_GET['TIMESTAMP']}";
            $sign = new HmacSign();
            if ($sign->sign($data, $sharedSecret) != $_GET['HMAC']) {
                throw new InvalidRequestException('incorect signature');
            }
        } elseif (strlen($sharedSecret) == 64) {
            $data = "{$_GET['VS']}{$_GET['RES']}";
            $sign = new Aes256Sign();
            if ($sign->sign($data, $sharedSecret) != $_GET['SIGN']) {
                throw new InvalidRequestException('incorect signature');
            }
        } elseif (strlen($sharedSecret) == 8) {
            $data = "{$_GET['VS']}{$_GET['RES']}";
            $sign = new DesSign();
            if ($sign->sign($data, $sharedSecret) != $_GET['SIGN']) {
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
