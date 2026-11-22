<?php

declare(strict_types=1);

namespace Omnipay\TatraPay\Message;

use Omnipay\Core\Message\AbstractRequest;
use Omnipay\Core\Sign\KeyType;

final class PurchaseRequest extends AbstractRequest
{
    /**
     * @param array<mixed> $parameters
     */
    #[\Override]
    public function initialize(array $parameters = []): static
    {
        parent::initialize($parameters);
        $this->setTimestamp(gmdate('dmYHis'));

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function getData(): array
    {
        $this->validate('sharedSecret', 'mid', 'vs', 'rurl');

        $data = [];
        $data['PT'] = 'TatraPay';
        $data['MID'] = $this->getMid();
        $data['CURR'] = $this->getCurrencyNumericCode();
        $data['VS'] = $this->getVs();
        $data['CS'] = $this->getCs();
        $data['AMT'] = $this->getAmount();
        $data['DESC'] = $this->getDesc();
        $data['RSMS'] = $this->getRsms();
        $data['REM'] = $this->getRem();
        $data['AREDIR'] = $this->getAredir();
        $data['LANG'] = $this->getLang();
        $data['RURL'] = $this->getRurl();

        if ($this->getKeyType() === KeyType::Hmac) {
            $data['TIMESTAMP'] = $this->getTimestamp();
        }

        return $data;
    }

    public function generateSignature(string $data): string
    {
        return $this->sign($data);
    }

    #[\Override]
    public function send(): PurchaseResponse
    {
        return $this->sendData($this->getData());
    }

    #[\Override]
    public function sendData(mixed $data): PurchaseResponse
    {
        $data = self::arrayData($data);
        $curr = $this->getCurrencyNumericCode();
        $input = "{$this->getMid()}{$this->getAmount()}{$curr}{$this->getVs()}{$this->getSs()}{$this->getCs()}{$this->getRurl()}";

        if ($this->getKeyType() === KeyType::Hmac) {
            $data['HMAC'] = $this->generateSignature("{$input}{$this->getRem()}{$this->getTimestamp()}");
        } else {
            $data['SIGN'] = $this->generateSignature($input);
        }

        return $this->response = new PurchaseResponse($this, $data);
    }

    public function getEndpoint(): string
    {
        $keyType = $this->getKeyType();

        if ($this->getTestMode()) {
            return $this->getTestEndpointHost() . match ($keyType) {
                KeyType::Hmac => '/payment/tatrapay-hmac',
                KeyType::Aes => '/payment/tatrapay-aes256',
                KeyType::Des => '/payment/tatrapay-des',
            };
        }

        return $keyType === KeyType::Hmac
            ? 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/tatrapay'
            : 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp';
    }
}
