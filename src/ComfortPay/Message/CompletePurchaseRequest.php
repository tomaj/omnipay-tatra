<?php

namespace Omnipay\ComfortPay\Message;

use Omnipay\Common\Currency;
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

        // todo: validate if $_GET['VS'] == $this->getVs()

        if (strlen($sharedSecret) == 128) {
            $curr = Currency::find($this->getCurrency())->getNumeric();
            $tid = isset($_GET['TID']) ? $_GET['TID'] : '';
            $data = "{$this->getAmount()}{$curr}{$this->getVs()}{$_GET['RES']}{$_GET['AC']}{$_GET['TRES']}{$_GET['CID']}{$_GET['CC']}{$_GET['RC']}{$tid}{$_GET['TIMESTAMP']}";
            $sign = new HmacSign();
            if ($sign->sign($data, $sharedSecret) != $_GET['HMAC']) {
                throw new InvalidRequestException('incorect signature');
            }
        } elseif (strlen($sharedSecret) == 64) {
            $data = "{$this->getVs()}{$_GET['TRES']}{$_GET['AC']}{$_GET['CID']}";
            $sign = new Aes256Sign();
            if ($sign->sign($data, $sharedSecret) != $_GET['SIGN']) {
                throw new InvalidRequestException('incorect signature');
            }
        } else {
            throw new \Exception('Unknown key length');
        }

        return [
            'RES' => $_GET['RES'],
            'VS' => $_GET['VS'],
            'CC' => isset($_GET['CC']) ? $_GET['CC'] : '',
            'CID' => $_GET['CID'],
            'TRES' => $_GET['TRES'],
        ];
    }
    public function sendData($data)
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
    }
}