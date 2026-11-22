<?php

declare(strict_types=1);

namespace Omnipay\ComfortPay;

use Omnipay\ComfortPay\Message\ChargeRequest;
use Omnipay\ComfortPay\Message\CheckCardRequest;
use Omnipay\ComfortPay\Message\CompletePurchaseRequest;
use Omnipay\ComfortPay\Message\ListOfExpiredRequest;
use Omnipay\ComfortPay\Message\ListOfExpirePerIdRequest;
use Omnipay\ComfortPay\Message\PurchaseRequest;
use Omnipay\ComfortPay\Message\TransactionStatusRequest;
use Omnipay\ComfortPay\Message\UnRegisterCardRequest;
use Omnipay\Common\AbstractGateway;
use Omnipay\Core\GatewayParametersTrait;

/**
 * ComfortPay: a CardPay payment that registers the card, and the SOAP web service that charges and manages
 * registered cards.
 */
final class Gateway extends AbstractGateway
{
    use GatewayParametersTrait;
    use ParametersTrait;

    /** @deprecated use TransactionType::Purchase */
    public const string TRANSACTION_TYPE_PURCHASE = 'PURCHASE';
    /** @deprecated use TransactionType::Preauthorization */
    public const string TRANSACTION_TYPE_PREAUTHORIZATION = 'PREAUTHORIZATION';
    /** @deprecated use TransactionType::PreauthConfirm */
    public const string TRANSACTION_TYPE_PREAUTH_CONFIRM = 'PREAUTH-CONFIRM';
    /** @deprecated use TransactionType::PreauthCancel */
    public const string TRANSACTION_TYPE_PREAUTH_CANCEL = 'PREAUTH-CANCEL';
    /** @deprecated use TransactionType::Chargeback */
    public const string TRANSACTION_TYPE_CHARGEBACK = 'CHARGEBACK';

    /** @deprecated use CardStatus::Ok */
    public const string CARD_STATUS_OK = 'OK';
    /** @deprecated use CardStatus::Fail */
    public const string CARD_STATUS_FAIL = 'FAIL';
    /** @deprecated use CardStatus::Unknown */
    public const string CARD_STATUS_UNKNOWN = 'UNKNOWN';

    #[\Override]
    public function getName(): string
    {
        return 'ComfortPay Gateway';
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
            'terminalId' => '',
            'ws' => '',
            'certPath' => '',
            'certPass' => '',
        ];
    }

    /**
     * Redirects the customer to pay by card and register it (TPAY=Y).
     *
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
     * Charges a registered card (doCardTransaction).
     *
     * @param array<string, mixed> $parameters
     */
    public function charge(array $parameters = []): ChargeRequest
    {
        return $this->createTypedRequest(ChargeRequest::class, $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function checkCard(array $parameters = []): CheckCardRequest
    {
        return $this->createTypedRequest(CheckCardRequest::class, $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function unRegisterCard(array $parameters = []): UnRegisterCardRequest
    {
        return $this->createTypedRequest(UnRegisterCardRequest::class, $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function listOfExpirePerId(array $parameters = []): ListOfExpirePerIdRequest
    {
        return $this->createTypedRequest(ListOfExpirePerIdRequest::class, $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function listOfExpired(array $parameters = []): ListOfExpiredRequest
    {
        return $this->createTypedRequest(ListOfExpiredRequest::class, $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function checkTransaction(array $parameters = []): TransactionStatusRequest
    {
        return $this->createTypedRequest(TransactionStatusRequest::class, $parameters);
    }
}
