<?php

declare(strict_types=1);

namespace Omnipay\ComfortPay\Message;

use Omnipay\ComfortPay\ParametersTrait;
use Omnipay\Core\Exception\UnsupportedKeyException;
use Omnipay\Core\Message\AbstractRequest;
use Omnipay\Core\Sign\KeyType;

/**
 * A CardPay payment that registers the card for later charges (TPAY=Y). HMAC or AES keys only.
 */
final class PurchaseRequest extends AbstractRequest
{
    use ParametersTrait;

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
        $this->setAredir(true);

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

    public function getTpay(): string
    {
        return 'Y';
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function getData(): array
    {
        $this->validate('sharedSecret', 'mid', 'vs', 'rurl');
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
        $data['AREDIR'] = $this->getAredir();
        $data['LANG'] = $this->getLang();
        $data['RURL'] = $this->getRurl();
        $data['IPC'] = $this->getIpc();
        $data['NAME'] = $this->getName();
        $data['TPAY'] = $this->getTpay();
        $rem = $this->getRem();
        if (!in_array($rem, [null, '', '0'], true)) {
            $data['REM'] = $rem;
        }
        $data['TEM'] = 1;

        if ($this->getKeyType() === KeyType::Hmac) {
            $data['TIMESTAMP'] = $this->getTimestamp();
        }

        return $data;
    }

    /**
     * @throws UnsupportedKeyException for a DES key, which ComfortPay does not support
     */
    public function generateSignature(string $data): string
    {
        $this->getComfortPayKeyType();

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
            $input = "{$this->getMid()}{$this->getAmount()}{$curr}{$this->getVs()}{$this->getRurl()}{$this->getIpc()}{$this->getName()}{$this->getRem()}{$this->getTpay()}{$this->getTimestamp()}";
            $data['HMAC'] = $this->generateSignature($input);
        } else {
            $input = "{$this->getMid()}{$this->getAmount()}{$curr}{$this->getVs()}{$this->getCs()}{$this->getRurl()}{$this->getIpc()}{$this->getName()}{$this->getTpay()}";
            $data['SIGN'] = $this->generateSignature($input);
        }

        return $this->response = new PurchaseResponse($this, $data);
    }

    public function getEndpoint(): string
    {
        $isHmac = $this->getComfortPayKeyType() === KeyType::Hmac;

        if ($this->getTestMode()) {
            return $this->getTestEndpointHost() . ($isHmac ? '/payment/comfortpay-hmac' : '/payment/comfortpay-aes256');
        }

        return $isHmac
            ? 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/cardpay'
            : 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp';
    }

    private function getComfortPayKeyType(): KeyType
    {
        $keyType = $this->getKeyType();
        if ($keyType === KeyType::Des) {
            throw new UnsupportedKeyException('Unknown key length 8: ComfortPay supports only 64 (AES) or 128 (HMAC) character keys');
        }

        return $keyType;
    }
}
