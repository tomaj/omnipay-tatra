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
     * Path to a CA bundle that verifies the web service's TLS certificate instead of the system CA store.
     */
    public function getCaFile(): ?string
    {
        return $this->getStringParameter('caFile');
    }

    public function setCaFile(?string $value): static
    {
        $this->setParameter('caFile', $value);
        return $this;
    }

    /**
     * A stream context (stream_context_create()) for the web service connection; replaces the default one, which
     * verifies the peer certificate and name.
     *
     * @return resource|null
     */
    public function getStreamContext(): mixed
    {
        $context = $this->getParameter('streamContext');

        return is_resource($context) ? $context : null;
    }

    /**
     * @param resource|null $value
     */
    public function setStreamContext(mixed $value): static
    {
        if ($value !== null && (!is_resource($value) || get_resource_type($value) !== 'stream-context')) {
            throw new \InvalidArgumentException('streamContext must be a stream context created by stream_context_create()');
        }

        $this->setParameter('streamContext', $value);
        return $this;
    }

    public function getProxyHost(): ?string
    {
        return $this->getStringParameter('proxyHost');
    }

    public function setProxyHost(?string $value): static
    {
        $this->setParameter('proxyHost', $value);
        return $this;
    }

    public function getProxyPort(): ?int
    {
        $value = $this->getParameter('proxyPort');

        return is_numeric($value) ? (int) $value : null;
    }

    public function setProxyPort(int|string|null $value): static
    {
        $this->setParameter('proxyPort', $value);
        return $this;
    }

    public function getProxyLogin(): ?string
    {
        return $this->getStringParameter('proxyLogin');
    }

    public function setProxyLogin(?string $value): static
    {
        $this->setParameter('proxyLogin', $value);
        return $this;
    }

    public function getProxyPassword(): ?string
    {
        return $this->getStringParameter('proxyPassword');
    }

    public function setProxyPassword(?string $value): static
    {
        $this->setParameter('proxyPassword', $value);
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
