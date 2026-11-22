<?php

declare(strict_types=1);

namespace Omnipay\ComfortPay\Message;

use Omnipay\Common\Message\AbstractResponse;

/**
 * The result of a charge or a transaction status query. transactionStatus 00 means approved; a declined charge that
 * the bank reported as a SOAP fault has no status.
 */
final class CardTransactionResponse extends AbstractResponse
{
    public const string STATUS_APPROVED = '00';

    #[\Override]
    public function isSuccessful(): bool
    {
        return $this->getTransactionStatus() === self::STATUS_APPROVED;
    }

    /**
     * @return string|false false when the bank returned no transaction (a declined charge)
     */
    #[\Override] // @phpstan-ignore method.childReturnType (false instead of null is kept for backward compatibility)
    public function getTransactionId(): string|false
    {
        return $this->getDataString('transactionId');
    }

    /**
     * @return string|false false when the bank returned no status (a declined charge)
     */
    public function getTransactionStatus(): string|false
    {
        return $this->getDataString('transactionStatus');
    }

    /**
     * @return string|false the approval code, false when the bank returned none
     */
    public function getTransactionApproval(): string|false
    {
        return $this->getDataString('transactionApproval');
    }

    private function getDataString(string $key): string|false
    {
        $value = is_array($this->data) ? $this->data[$key] ?? null : null;

        return is_string($value) ? $value : false;
    }
}
