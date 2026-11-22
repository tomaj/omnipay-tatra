<?php

declare(strict_types=1);

namespace Omnipay\ComfortPay\Exception;

use Omnipay\Core\Exception\TatraException;

/**
 * A SOAP fault from the ComfortPay web service, with the bank's error code from the fault detail.
 *
 * It extends \SoapFault, so code that catches \SoapFault keeps working.
 */
final class ComfortPaySoapFault extends \SoapFault implements TatraException
{
    /** General error while processing the transaction */
    public const int GENERAL_ERROR = 50000;
    /** Timeout while processing the transaction: the result is not known yet */
    public const int TIMEOUT = 50001;
    /** The registered card (CID) does not exist */
    public const int UNKNOWN_CARD = 50051;
    /** Incorrect merchant id */
    public const int INVALID_MERCHANT = 50052;
    /** Negative amount */
    public const int NEGATIVE_AMOUNT = 50053;
    /** Duplicate transaction id */
    public const int DUPLICATE_TRANSACTION = 50054;
    /** VS or SS longer than 10 digits */
    public const int SYMBOL_TOO_LONG = 50055;
    /** E2E reference longer than 35 characters */
    public const int E2E_TOO_LONG = 50056;
    /** Error in parentTransactionId */
    public const int INVALID_PARENT_TRANSACTION = 50057;
    /** VS or SS contains non-numeric characters */
    public const int SYMBOL_NOT_NUMERIC = 50058;
    /** The amount exceeds the original authorization */
    public const int AMOUNT_EXCEEDS_AUTHORIZATION = 50059;
    /** No parent transaction with this VS/E2E, type, state and currency in the last 365 days */
    public const int PARENT_TRANSACTION_NOT_FOUND = 50060;

    private ?int $errorCode = null;

    public static function fromSoapFault(\SoapFault $fault): self
    {
        $exception = new self(
            $fault->faultcode ?? 'Receiver',
            $fault->faultstring,
            $fault->faultactor,
            $fault->detail,
            $fault->_name,
            $fault->headerfault,
        );
        $exception->errorCode = self::extractErrorCode($fault->detail);

        return $exception;
    }

    /**
     * The bank's error code (types:ExceptionType/errorCode in the fault detail), null when the fault has none.
     */
    public function getErrorCode(): ?int
    {
        return $this->errorCode;
    }

    /**
     * Whether the bank reported the fault as its own (SOAP-ENV:Receiver), which it does when it declines a transaction.
     */
    public function isReceiverFault(): bool
    {
        return $this->faultcode === 'SOAP-ENV:Receiver';
    }

    private static function extractErrorCode(mixed $detail): ?int
    {
        $exceptionType = is_object($detail) && property_exists($detail, 'ExceptionType') ? $detail->ExceptionType : null;
        $errorCode = is_object($exceptionType) && property_exists($exceptionType, 'errorCode') ? $exceptionType->errorCode : null;

        return is_numeric($errorCode) ? (int) $errorCode : null;
    }
}
