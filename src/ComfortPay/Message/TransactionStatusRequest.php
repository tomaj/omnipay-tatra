<?php

declare(strict_types=1);

namespace Omnipay\ComfortPay\Message;

/**
 * The status of an earlier charge (getTransactionStatus).
 */
final class TransactionStatusRequest extends AbstractSoapRequest
{
    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function getData(): array
    {
        $this->validate('transactionId');

        $data = parent::getData();
        $data['transactionId'] = $this->getTransactionIdString();

        return $data;
    }

    #[\Override]
    public function send(): CardTransactionResponse
    {
        return $this->sendData($this->getData());
    }

    /**
     * @throws \SoapFault
     */
    #[\Override]
    public function sendData(mixed $data): CardTransactionResponse
    {
        $data = self::arrayData($data);

        if ($this->getTestMode()) {
            // test mode: an even transaction id is declined (02), an odd one approved (00)
            $status = self::isEven($data['transactionId']) ? '02' : '00';

            return $this->response = new CardTransactionResponse(
                $this,
                ['transactionId' => $data['transactionId'], 'transactionStatus' => $status, 'transactionApproval' => '123'],
            );
        }

        $request = new \stdClass();
        $request->transactionId = $data['transactionId'];

        return $this->response = new CardTransactionResponse($this, self::transactionResult($this->call('getTransactionStatus', $request)));
    }
}
