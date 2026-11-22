<?php

declare(strict_types=1);

namespace Omnipay\CardPay;

use Omnipay\CardPay\Message\AuthorizeRequest;
use Omnipay\CardPay\Message\CancelAuthorizeRequest;
use Omnipay\CardPay\Message\CompleteAuthorizeRequest;
use Omnipay\CardPay\Message\CompletePurchaseRequest;
use Omnipay\CardPay\Message\PurchaseRequest;
use Omnipay\Common\AbstractGateway;
use Omnipay\Core\GatewayParametersTrait;

/**
 * CardPay: card payments, pre-authorization and its capture, reversal or refund.
 */
final class Gateway extends AbstractGateway
{
    use GatewayParametersTrait;

    #[\Override]
    public function getName(): string
    {
        return 'CardPay Gateway';
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

    /**
     * Pre-authorization (TXN=PA), HMAC keys only.
     *
     * @param array<string, mixed> $parameters
     */
    public function authorize(array $parameters = []): AuthorizeRequest
    {
        return $this->createTypedRequest(AuthorizeRequest::class, $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function completeAuthorize(array $parameters = []): CompleteAuthorizeRequest
    {
        return $this->createTypedRequest(CompleteAuthorizeRequest::class, $parameters);
    }

    /**
     * Capture (CPA) or reversal (SPA) of a pre-authorization, or a refund (CB), HMAC keys only.
     *
     * @param array<string, mixed> $parameters
     */
    public function cancelAuthorize(array $parameters = []): CancelAuthorizeRequest
    {
        return $this->createTypedRequest(CancelAuthorizeRequest::class, $parameters);
    }
}
