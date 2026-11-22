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
        $vs = $_GET['VS'] ?? '';
        $ac = $_GET['AC'] ?? '';
        $res = $_GET['RES'] ?? '';
        $tres = $_GET['TRES'] ?? '';
        $tid = $_GET['TID'] ?? '';
        $cc = $_GET['CC'] ?? '';
        $rc = $_GET['RC'] ?? '';
        $txn = $_GET['TXN'] ?? '';
        $cid = $_GET['CID'] ?? '';
        $timestamp = $_GET['TIMESTAMP'] ?? '';

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
            'CC' => $cc,
            'AC' => $ac,
        ];
    }

    public function sendData($data)
    {
        return $this->response = new CompleteAuthorizeResponse($this, $data);
    }
}
