<?php

declare(strict_types=1);

namespace Omnipay\ComfortPay\Message;

use Omnipay\ComfortPay\ParametersTrait;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Core\Exception\UnsupportedKeyException;
use Omnipay\Core\Message\AbstractRequest;
use Omnipay\Core\Sign\KeyType;
use Omnipay\Core\Sign\Signature;

/**
 * Verifies the customer's return from a ComfortPay payment; CID is the id of the registered card.
 */
final class CompletePurchaseRequest extends AbstractRequest
{
    use ParametersTrait;

    /**
     * @return array{RES: string, VS: string, CC: string, CID: string, TRES: string, RC: string}
     * @throws InvalidRequestException when the bank returned another VS or the signature is wrong
     */
    #[\Override]
    public function getData(): array
    {
        $vs = $this->getQueryParameter('VS');
        $ac = $this->getQueryParameter('AC');
        $cid = $this->getQueryParameter('CID');
        $res = $this->getQueryParameter('RES');
        $tres = $this->getQueryParameter('TRES');
        $tid = $this->getQueryParameter('TID');
        $cc = $this->getQueryParameter('CC');
        $rc = $this->getQueryParameter('RC');
        $timestamp = $this->getQueryParameter('TIMESTAMP');

        if ($vs !== (string) $this->getVs()) {
            throw new InvalidRequestException('Variable symbol mismatch');
        }

        $keyType = $this->getKeyType();
        if ($keyType === KeyType::Hmac) {
            $curr = $this->getCurrencyNumericCode();
            $data = "{$this->getAmount()}{$curr}{$vs}{$res}{$ac}{$tres}{$cid}{$cc}{$rc}{$tid}{$timestamp}";
            $received = $this->getQueryParameter('HMAC');
        } elseif ($keyType === KeyType::Aes) {
            $data = "{$vs}{$tres}{$ac}{$cid}";
            $received = $this->getQueryParameter('SIGN');
        } else {
            throw new UnsupportedKeyException('Unknown key length 8: ComfortPay supports only 64 (AES) or 128 (HMAC) character keys');
        }

        if (!Signature::equals($this->sign($data), $received)) {
            throw new InvalidRequestException('incorect signature');
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

    #[\Override]
    public function send(): CompletePurchaseResponse
    {
        return $this->sendData($this->getData());
    }

    #[\Override]
    public function sendData(mixed $data): CompletePurchaseResponse
    {
        return $this->response = new CompletePurchaseResponse($this, self::arrayData($data));
    }
}
