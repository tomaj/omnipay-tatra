<?php

declare(strict_types=1);

namespace Omnipay\CardPay\Message;

use Omnipay\Core\Message\AbstractRequest;
use Omnipay\Core\Sign\HmacSign;

/**
 * CardPay pre-authorization (TXN=PA) with 3-D Secure data. Signed with HMAC; the shared secret must be the 128-character key.
 */
final class AuthorizeRequest extends AbstractRequest
{
    public const string TXN_VALUE = 'PA';

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

    public function getTpay(): ?string
    {
        return $this->getStringParameter('tpay');
    }

    public function setTpay(?string $value): static
    {
        return $this->setParameter('tpay', $value);
    }

    public function getTxn(): string
    {
        return self::TXN_VALUE;
    }

    public function getE2E(): ?string
    {
        return $this->getStringParameter('E2E');
    }

    public function setE2E(?string $value): static
    {
        return $this->setParameter('E2E', $value);
    }

    public function getCid(): ?string
    {
        return $this->getStringParameter('CID');
    }

    public function setCid(?string $value): static
    {
        return $this->setParameter('CID', $value);
    }

    public function getEcid(): ?string
    {
        return $this->getStringParameter('ECID');
    }

    public function setEcid(?string $value): static
    {
        return $this->setParameter('ECID', $value);
    }

    public function getTdsCardholder(): ?string
    {
        return $this->getStringParameter('TDS_CARDHOLDER');
    }

    public function setTdsCardholder(?string $value): static
    {
        return $this->setParameter('TDS_CARDHOLDER', $value);
    }

    public function getTdsEmail(): ?string
    {
        return $this->getStringParameter('TDS_EMAIL');
    }

    public function setTdsEmail(?string $value): static
    {
        return $this->setParameter('TDS_EMAIL', $value);
    }

    public function getTdsMobilePhone(): ?string
    {
        return $this->getStringParameter('TDS_MOBILE_PHONE');
    }

    public function setTdsMobilePhone(?string $value): static
    {
        return $this->setParameter('TDS_MOBILE_PHONE', $value);
    }

    public function getTdsBillCity(): ?string
    {
        return $this->getStringParameter('TDS_BILL_CITY');
    }

    public function setTdsBillCity(?string $value): static
    {
        return $this->setParameter('TDS_BILL_CITY', $value);
    }

    public function getTdsBillCountry(): ?string
    {
        return $this->getStringParameter('TDS_BILL_COUNTRY');
    }

    public function setTdsBillCountry(?string $value): static
    {
        return $this->setParameter('TDS_BILL_COUNTRY', $value);
    }

    public function getTdsBillAddress1(): ?string
    {
        return $this->getStringParameter('TDS_BILL_ADDRESS1');
    }

    public function setTdsBillAddress1(?string $value): static
    {
        return $this->setParameter('TDS_BILL_ADDRESS1', $value);
    }

    public function getTdsBillAddress2(): ?string
    {
        return $this->getStringParameter('TDS_BILL_ADDRESS2');
    }

    public function setTdsBillAddress2(?string $value): static
    {
        return $this->setParameter('TDS_BILL_ADDRESS2', $value);
    }

    public function getTdsBillZip(): ?string
    {
        return $this->getStringParameter('TDS_BILL_ZIP');
    }

    public function setTdsBillZip(?string $value): static
    {
        return $this->setParameter('TDS_BILL_ZIP', $value);
    }

    public function getTdsShipCity(): ?string
    {
        return $this->getStringParameter('TDS_SHIP_CITY');
    }

    public function setTdsShipCity(?string $value): static
    {
        return $this->setParameter('TDS_SHIP_CITY', $value);
    }

    public function getTdsShipCountry(): ?string
    {
        return $this->getStringParameter('TDS_SHIP_COUNTRY');
    }

    public function setTdsShipCountry(?string $value): static
    {
        return $this->setParameter('TDS_SHIP_COUNTRY', $value);
    }

    public function getTdsShipAddress1(): ?string
    {
        return $this->getStringParameter('TDS_SHIP_ADDRESS1');
    }

    public function setTdsShipAddress1(?string $value): static
    {
        return $this->setParameter('TDS_SHIP_ADDRESS1', $value);
    }

    public function getTdsShipAddress2(): ?string
    {
        return $this->getStringParameter('TDS_SHIP_ADDRESS2');
    }

    public function setTdsShipAddress2(?string $value): static
    {
        return $this->setParameter('TDS_SHIP_ADDRESS2', $value);
    }

    public function getTdsShipZip(): ?string
    {
        return $this->getStringParameter('TDS_SHIP_ZIP');
    }

    public function setTdsShipZip(?string $value): static
    {
        return $this->setParameter('TDS_SHIP_ZIP', $value);
    }

    public function getTdsAddrMatch(): ?string
    {
        return $this->getStringParameter('TDS_ADDR_MATCH');
    }

    public function setTdsAddrMatch(?string $value): static
    {
        return $this->setParameter('TDS_ADDR_MATCH', $value);
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function getData(): array
    {
        $this->validate('sharedSecret', 'mid', 'currency', 'vs', 'rurl', 'ipc', 'name', 'timestamp');

        $data = [];
        $data['PT'] = 'CardPay';

        $data['MID'] = $this->getMid();
        $data['AMT'] = $this->getAmount();
        $data['CURR'] = $this->getCurrencyNumericCode();
        $data['VS'] = $this->getVs();
        $data['RURL'] = $this->getRurl();
        $data['IPC'] = $this->getIpc();
        $data['NAME'] = $this->getName();
        $data['TIMESTAMP'] = $this->getTimestamp();

        $data['E2E'] = $this->getE2E();
        $data['TXN'] = $this->getTxn();
        $data['REM'] = $this->getRem();
        $data['TPAY'] = $this->getTpay();
        $data['CID'] = $this->getCid();
        $data['ECID'] = $this->getEcid();

        $data['TDS_CARDHOLDER'] = $this->getTdsCardholder();
        $data['TDS_EMAIL'] = $this->getTdsEmail();
        $data['TDS_MOBILE_PHONE'] = $this->getTdsMobilePhone();
        $data['TDS_BILL_CITY'] = $this->getTdsBillCity();
        $data['TDS_BILL_COUNTRY'] = $this->getTdsBillCountry();
        $data['TDS_BILL_ADDRESS1'] = $this->getTdsBillAddress1();
        $data['TDS_BILL_ADDRESS2'] = $this->getTdsBillAddress2();
        $data['TDS_BILL_ZIP'] = $this->getTdsBillZip();
        $data['TDS_SHIP_CITY'] = $this->getTdsShipCity();
        $data['TDS_SHIP_COUNTRY'] = $this->getTdsShipCountry();
        $data['TDS_SHIP_ADDRESS1'] = $this->getTdsShipAddress1();
        $data['TDS_SHIP_ADDRESS2'] = $this->getTdsShipAddress2();
        $data['TDS_SHIP_ZIP'] = $this->getTdsShipZip();
        $data['TDS_ADDR_MATCH'] = $this->getTdsAddrMatch();

        $data['AREDIR'] = $this->getAredir();
        $data['LANG'] = $this->getLang();

        return $data;
    }

    public function generateSignature(string $data): string
    {
        return new HmacSign()->sign($data, $this->getSharedSecret() ?? '');
    }

    #[\Override]
    public function send(): AuthorizeResponse
    {
        return $this->sendData($this->getData());
    }

    #[\Override]
    public function sendData(mixed $data): AuthorizeResponse
    {
        $data = self::arrayData($data);
        $curr = $this->getCurrencyNumericCode();

        $input = "{$this->getMid()}{$this->getAmount()}{$curr}{$this->getVs()}{$this->getE2E()}{$this->getTxn()}{$this->getRurl()}";
        $input .= "{$this->getIpc()}{$this->getName()}{$this->getRem()}{$this->getTpay()}{$this->getCid()}{$this->getEcid()}";
        $input .= "{$this->getTdsCardholder()}{$this->getTdsEmail()}{$this->getTdsMobilePhone()}{$this->getTdsBillCity()}";
        $input .= "{$this->getTdsBillCountry()}{$this->getTdsBillAddress1()}{$this->getTdsBillAddress2()}{$this->getTdsBillZip()}";
        $input .= "{$this->getTdsShipCity()}{$this->getTdsShipCountry()}{$this->getTdsShipAddress1()}{$this->getTdsShipAddress2()}";
        $input .= "{$this->getTdsShipZip()}{$this->getTdsAddrMatch()}{$this->getTimestamp()}";

        $data['HMAC'] = $this->generateSignature($input);

        return $this->response = new AuthorizeResponse($this, $data);
    }

    public function getEndpoint(): string
    {
        if ($this->getTestMode()) {
            return $this->getTestEndpointHost() . '/payment/cardpay-authorize-hmac';
        }

        return 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/cardpay';
    }
}
