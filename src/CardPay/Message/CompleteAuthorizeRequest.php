<?php

namespace Omnipay\CardPay\Message;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Core\Message\AbstractRequest;
use Omnipay\Core\Sign\HmacSign;

class CompleteAuthorizeRequest extends AbstractRequest
{
    public function getData()
    {
        $vs = isset($_GET['VS']) ? $_GET['VS'] : '';
        $ac = isset($_GET['AC']) ? $_GET['AC'] : '';
        $res = isset($_GET['RES']) ? $_GET['RES'] : '';
        $tres = isset($_GET['TRES']) ? $_GET['TRES'] : '';
        $tid = isset($_GET['TID']) ? $_GET['TID'] : '';
        $cc = isset($_GET['CC']) ? $_GET['CC'] : '';
        $rc = isset($_GET['RC']) ? $_GET['RC'] : '';
        $txn = isset($_GET['TXN']) ? $_GET['TXN'] : '';
        $cid = isset($_GET['CID']) ? $_GET['CID'] : '';
        $timestamp = isset($_GET['TIMESTAMP']) ? $_GET['TIMESTAMP'] : '';

        if ($vs != $this->getVs()) {
            throw new InvalidRequestException('Variable symbol mismatch');
        }

        $curr = (new ISOCurrencies())->numericCodeFor(new Currency($this->getCurrency()));
        $data = "{$this->getAmount()}{$curr}{$this->getVs()}{$txn}{$res}{$ac}{$tres}{$cid}{$cc}{$rc}{$tid}{$timestamp}";

        $sign = new HmacSign();
        if ($sign->sign($data, $this->getParameter('sharedSecret')) != $_GET['HMAC']) {
            throw new InvalidRequestException('incorect signature');
        }

        return [
            'RES' => $res,
            'VS' => $vs,
            'RC' => $rc,
            'TRES' => $tres,
            'TID' => $tid,
            'CID' => $cid,
        ];
    }

    public function sendData($data)
    {
        return $this->response = new CompleteAuthorizeResponse($this, $data);
    }
}
