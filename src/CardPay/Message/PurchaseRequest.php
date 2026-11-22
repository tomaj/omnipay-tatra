<?php

declare(strict_types=1);

namespace Omnipay\CardPay\Message;

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
        $remoteAddress = $this->httpRequest->server->get('REMOTE_ADDR');
        if ($this->getIpc() === null && is_string($remoteAddress)) {
            $this->setIpc($remoteAddress);
        }

        return $this;
    }

    public function getIpc(): ?string
    {
        return $this->getStringParameter('ipc');
    }

    public function setIpc(?string $value): static
    {
        return $this->setParameter('ipc', $value);
    }

    public function getName(): ?string
    {
        return $this->getStringParameter('name');
    }

    public function setName(?string $value): static
    {
        return $this->setParameter('name', $value);
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function getData(): array
    {
        $this->validate('sharedSecret', 'mid', 'vs', 'rurl', 'ipc', 'name');
        self::validateCustomerName($this->getName());

        $data = [];
        $data['PT'] = 'CardPay';
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
        $data['IPC'] = $this->getIpc();
        $data['NAME'] = $this->getName();

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

        if ($this->getKeyType() === KeyType::Hmac) {
            $input = "{$this->getMid()}{$this->getAmount()}{$curr}{$this->getVs()}{$this->getRurl()}{$this->getIpc()}{$this->getName()}{$this->getRem()}{$this->getTimestamp()}";
            $data['HMAC'] = $this->generateSignature($input);
        } else {
            $input = "{$this->getMid()}{$this->getAmount()}{$curr}{$this->getVs()}{$this->getCs()}{$this->getRurl()}{$this->getIpc()}{$this->getName()}";
            $data['SIGN'] = $this->generateSignature($input);
        }

        return $this->response = new PurchaseResponse($this, $data);
    }

    public function getEndpoint(): string
    {
        $keyType = $this->getKeyType();

        if ($this->getTestMode()) {
            return $this->getTestEndpointHost() . match ($keyType) {
                KeyType::Hmac => '/payment/cardpay-hmac',
                KeyType::Aes => '/payment/cardpay-aes256',
                KeyType::Des => '/payment/cardpay-des',
            };
        }

        return $keyType === KeyType::Hmac
            ? 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/cardpay'
            : 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp';
    }
}
