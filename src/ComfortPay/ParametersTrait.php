<?php

declare(strict_types=1);

namespace Omnipay\ComfortPay;

/**
 * ComfortPay parameters shared by the gateway and its requests.
 */
trait ParametersTrait
{
    public function getTerminalId(): ?string
    {
        return $this->getStringParameter('terminalId');
    }

    public function setTerminalId(?string $value): static
    {
        $this->setParameter('terminalId', $value);
        return $this;
    }

    /**
     * The merchant id for the ComfortPay web service (merchantId in the SOAP requests).
     */
    public function getWs(): ?string
    {
        return $this->getStringParameter('ws');
    }

    public function setWs(?string $value): static
    {
        $this->setParameter('ws', $value);
        return $this;
    }

    /**
     * Path to the PEM file with the client certificate and private key for the web service.
     */
    public function getCertPath(): ?string
    {
        return $this->getStringParameter('certPath');
    }

    public function setCertPath(?string $value): static
    {
        $this->setParameter('certPath', $value);
        return $this;
    }

    /**
     * Passphrase of the client certificate's private key.
     */
    public function getCertPass(): ?string
    {
        return $this->getStringParameter('certPass');
    }

    public function setCertPass(?string $value): static
    {
        $this->setParameter('certPass', $value);
        return $this;
    }

    /**
     * A SoapClient to use instead of the one created from the bundled WSDL (for a custom transport or tests).
     */
    public function getSoapClient(): ?\SoapClient
    {
        $client = $this->getParameter('soapClient');

        return $client instanceof \SoapClient ? $client : null;
    }

    public function setSoapClient(?\SoapClient $value): static
    {
        $this->setParameter('soapClient', $value);
        return $this;
    }
}
