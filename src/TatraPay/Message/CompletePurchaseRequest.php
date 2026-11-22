<?php

declare(strict_types=1);

namespace Omnipay\TatraPay\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Core\Message\AbstractRequest;
use Omnipay\Core\Sign\KeyType;
use Omnipay\Core\Sign\Signature;

/**
 * Verifies the customer's return from TatraPay (the query string of the return URL).
 */
final class CompletePurchaseRequest extends AbstractRequest
{
    /**
     * @return array{RES: string, VS: string}
     * @throws InvalidRequestException when `vs` is missing, the bank returned another VS, or the signature is wrong
     */
    #[\Override]
    public function getData(): array
    {
        $expectedVs = $this->getVs();
        if ($expectedVs === null) {
            throw new InvalidRequestException('The vs parameter is required');
        }
        $res = $this->getQueryParameter('RES');

        if ($this->getQueryParameter('VS') !== $expectedVs) {
            throw new InvalidRequestException('Variable symbol mismatch');
        }

        if ($this->getKeyType() === KeyType::Hmac) {
            $curr = $this->getCurrencyNumericCode();
            $tid = $this->getQueryParameter('TID');
            $timestamp = $this->getQueryParameter('TIMESTAMP');
            $data = "{$this->getAmount()}{$curr}{$expectedVs}{$this->getSs()}{$this->getCs()}{$res}{$tid}{$timestamp}";
            $received = $this->getQueryParameter('HMAC');
        } else {
            $data = "{$expectedVs}{$this->getSs()}{$res}";
            $received = $this->getQueryParameter('SIGN');
        }

        if (!Signature::equals($this->sign($data), $received)) {
            throw new InvalidRequestException('incorect signature');
        }

        return [
            'RES' => $res,
            'VS' => $expectedVs,
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
