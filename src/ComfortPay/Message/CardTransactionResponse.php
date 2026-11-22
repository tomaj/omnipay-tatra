<?php

declare(strict_types=1);

namespace Omnipay\ComfortPay\Message;

use Omnipay\ComfortPay\Exception\ComfortPaySoapFault;
use Omnipay\Common\Message\AbstractResponse;

/**
 * The result of a charge or a transaction status query. transactionStatus 00 means approved; a declined charge that
 * the bank reported as a SOAP fault has no status and carries the bank's error code instead.
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
     * True when the bank timed out (error code 50001) and the result of the charge is not known yet: check it later
     * with checkTransaction() instead of charging again.
     */
    #[\Override]
    public function isPending(): bool
    {
        return $this->getErrorCode() === ComfortPaySoapFault::TIMEOUT;
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

    /**
     * The bank's error code of a declined charge (see ComfortPaySoapFault), null when the bank returned a status.
     */
    public function getErrorCode(): ?int
    {
        $errorCode = is_array($this->data) ? $this->data['errorCode'] ?? null : null;

        return is_int($errorCode) ? $errorCode : null;
    }

    #[\Override]
    public function getCode(): ?string
    {
        $errorCode = $this->getErrorCode();
        if ($errorCode !== null) {
            return (string) $errorCode;
        }

        $status = $this->getTransactionStatus();

        return $status !== false ? $status : null;
    }

    private function getDataString(string $key): string|false
    {
        $value = is_array($this->data) ? $this->data[$key] ?? null : null;

        return is_string($value) ? $value : false;
    }
}
