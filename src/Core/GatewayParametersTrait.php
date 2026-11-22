<?php

declare(strict_types=1);

namespace Omnipay\Core;

use Omnipay\Common\Message\AbstractRequest;

/**
 * Parameters shared by all Tatra banka gateways.
 */
trait GatewayParametersTrait
{
    public function getMid(): ?string
    {
        return $this->getStringParameter('mid');
    }

    public function setMid(?string $value): static
    {
        $this->setParameter('mid', $value);
        return $this;
    }

    public function getSharedSecret(): ?string
    {
        return $this->getStringParameter('sharedSecret');
    }

    public function setSharedSecret(?string $value): static
    {
        $this->setParameter('sharedSecret', $value);
        return $this;
    }

    public function getTestHost(): ?string
    {
        return $this->getStringParameter('testHost');
    }

    public function setTestHost(?string $value): static
    {
        $this->setParameter('testHost', $value);
        return $this;
    }

    private function getStringParameter(string $key): ?string
    {
        $value = $this->getParameter($key);
        if (is_string($value)) {
            return $value;
        }
        if (is_int($value)) {
            return (string) $value;
        }

        return null;
    }

    /**
     * @template T of AbstractRequest
     * @param class-string<T> $class
     * @param array<string, mixed> $parameters
     * @return T
     */
    private function createTypedRequest(string $class, array $parameters): AbstractRequest
    {
        /** @var T */
        return $this->createRequest($class, $parameters);
    }
}
