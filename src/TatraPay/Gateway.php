<?php

declare(strict_types=1);

namespace Omnipay\TatraPay;

use Omnipay\Common\AbstractGateway;
use Omnipay\Core\GatewayParametersTrait;
use Omnipay\TatraPay\Message\CompletePurchaseRequest;
use Omnipay\TatraPay\Message\PurchaseRequest;

/**
 * TatraPay: the customer pays from Tatra banka internet banking.
 */
final class Gateway extends AbstractGateway
{
    use GatewayParametersTrait;

    #[\Override]
    public function getName(): string
    {
        return 'TatraPay Gateway';
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public function getDefaultParameters(): array
    {
        return [
            'mid' => '',
            'sharedSecret' => '',
            'testHost' => '',
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function purchase(array $parameters = []): PurchaseRequest
    {
        return $this->createTypedRequest(PurchaseRequest::class, $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function completePurchase(array $parameters = []): CompletePurchaseRequest
    {
        return $this->createTypedRequest(CompletePurchaseRequest::class, $parameters);
    }
}
