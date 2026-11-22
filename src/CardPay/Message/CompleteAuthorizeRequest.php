<?php

declare(strict_types=1);

namespace Omnipay\CardPay\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Core\Message\AbstractRequest;
use Omnipay\Core\Sign\HmacSign;
use Omnipay\Core\Sign\Signature;

/**
 * Verifies the customer's return from a CardPay pre-authorization (HMAC).
 */
final class CompleteAuthorizeRequest extends AbstractRequest
{
    /**
     * @return array{RES: string, VS: string, RC: string, TRES: string, TID: string, CID: string, CC: string, AC: string}
     * @throws InvalidRequestException when the bank returned another VS or the signature is wrong
     */
    #[\Override]
    public function getData(): array
    {
        $vs = $this->getQueryParameter('VS');
        $ac = $this->getQueryParameter('AC');
        $res = $this->getQueryParameter('RES');
        $tres = $this->getQueryParameter('TRES');
        $tid = $this->getQueryParameter('TID');
        $cc = $this->getQueryParameter('CC');
        $rc = $this->getQueryParameter('RC');
        $txn = $this->getQueryParameter('TXN');
        $cid = $this->getQueryParameter('CID');
        $timestamp = $this->getQueryParameter('TIMESTAMP');

        if ($vs !== (string) $this->getVs()) {
            throw new InvalidRequestException('Variable symbol mismatch');
        }

        $curr = $this->getCurrencyNumericCode();
        $data = "{$this->getAmount()}{$curr}{$vs}{$txn}{$res}{$ac}{$tres}{$cid}{$cc}{$rc}{$tid}{$timestamp}";

        $signature = new HmacSign()->sign($data, $this->getSharedSecret() ?? '');
        if (!Signature::equals($signature, $this->getQueryParameter('HMAC'))) {
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

    #[\Override]
    public function send(): CompleteAuthorizeResponse
    {
        return $this->sendData($this->getData());
    }

    #[\Override]
    public function sendData(mixed $data): CompleteAuthorizeResponse
    {
        return $this->response = new CompleteAuthorizeResponse($this, self::arrayData($data));
    }
}
