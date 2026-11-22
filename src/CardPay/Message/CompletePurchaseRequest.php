<?php

declare(strict_types=1);

namespace Omnipay\CardPay\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Core\Message\AbstractRequest;
use Omnipay\Core\Sign\KeyType;
use Omnipay\Core\Sign\Signature;

/**
 * Verifies the customer's return from CardPay (the query string of the return URL).
 */
final class CompletePurchaseRequest extends AbstractRequest
{
    /**
     * @return array{RES: string, VS: string, RC: string}
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
        $timestamp = $this->getQueryParameter('TIMESTAMP');

        if ($vs !== (string) $this->getVs()) {
            throw new InvalidRequestException('Variable symbol mismatch');
        }

        $keyType = $this->getKeyType();
        if ($keyType === KeyType::Hmac) {
            $curr = $this->getCurrencyNumericCode();
            $data = "{$this->getAmount()}{$curr}{$vs}{$res}{$ac}{$tres}{$cc}{$rc}{$tid}{$timestamp}";
            $received = $this->getQueryParameter('HMAC');
            if (!Signature::equals($this->sign($data), $received)) {
                throw new InvalidRequestException("incorrect signature for data: '{$data}', sign: '{$received}'");
            }
        } elseif (!Signature::equals($this->sign("{$vs}{$res}{$ac}"), $this->getQueryParameter('SIGN'))) {
            throw new InvalidRequestException('incorect signature');
        }

        return [
            'RES' => $res,
            'VS' => $vs,
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
