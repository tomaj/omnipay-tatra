<?php

declare(strict_types=1);

namespace Omnipay\ComfortPay\Message;

use Omnipay\ComfortPay\TransactionType;
use Omnipay\Core\Exception\InvalidParameterException;

/**
 * Charges a registered card (doCardTransaction).
 */
final class ChargeRequest extends AbstractSoapRequest
{
    public function getTransactionType(): ?string
    {
        return $this->getStringParameter('transactionType');
    }

    /**
     * @throws InvalidParameterException for a value that is not a TransactionType
     */
    public function setTransactionType(TransactionType|string|null $value): static
    {
        if (is_string($value)) {
            $value = TransactionType::tryFrom($value) ?? throw new InvalidParameterException("Unsupported transactionType: {$value}");
        }

        return $this->setParameter('transactionType', $value?->value);
    }

    public function getParentTransactionId(): ?string
    {
        return $this->getStringParameter('parentTransactionId');
    }

    public function setParentTransactionId(?string $value): static
    {
        return $this->setParameter('parentTransactionId', $value);
    }

    /**
     * The registered card id (CID) to charge.
     */
    public function getReferedCardId(): ?string
    {
        return $this->getStringParameter('referedCardId');
    }

    public function setReferedCardId(?string $value): static
    {
        return $this->setParameter('referedCardId', $value);
    }

    public function getE2eReference(): ?string
    {
        return $this->getStringParameter('e2eReference');
    }

    public function setE2eReference(?string $value): static
    {
        return $this->setParameter('e2eReference', $value);
    }

    public function getSubmerchantId(): ?string
    {
        return $this->getStringParameter('submerchantId');
    }

    public function setSubmerchantId(?string $value): static
    {
        return $this->setParameter('submerchantId', $value);
    }

    public function getLocation(): ?string
    {
        return $this->getStringParameter('location');
    }

    public function setLocation(?string $value): static
    {
        return $this->setParameter('location', $value);
    }

    public function getCity(): ?string
    {
        return $this->getStringParameter('city');
    }

    public function setCity(?string $value): static
    {
        return $this->setParameter('city', $value);
    }

    public function getAlpha2CountryCode(): ?string
    {
        return $this->getStringParameter('alpha2CountryCode');
    }

    public function setAlpha2CountryCode(?string $value): static
    {
        return $this->setParameter('alpha2CountryCode', $value);
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function getData(): array
    {
        $this->validate('terminalId', 'amount', 'transactionId', 'transactionType', 'referedCardId', 'ws', 'currency');

        if (TransactionType::from((string) $this->getTransactionType())->requiresParentTransaction()) {
            $this->validate('parentTransactionId');
        }

        $data = array_merge(parent::getData(), [
            'transactionType' => $this->getTransactionType(),
            'transactionId' => $this->getTransactionIdString(),
            'parentTransactionId' => $this->getParentTransactionId(),
            'referedCardId' => $this->getReferedCardId(),
            'merchantId' => $this->getWs(),
            'terminalId' => $this->getTerminalId(),
            'amount' => $this->getAmount(),
            'cc' => $this->getCurrency(),
            'vs' => $this->getVs(),
            'ss' => $this->getSs(),
            'e2eReference' => $this->getE2eReference(),
            'submerchantId' => $this->getSubmerchantId(),
            'location' => $this->getLocation(),
            'city' => $this->getCity(),
            'alpha2CountryCode' => $this->getAlpha2CountryCode(),
        ]);

        if ($this->isEmpty($data['e2eReference'])) {
            $this->validate('ss', 'vs');
        }

        if ($this->isEmpty($data['ss']) && $this->isEmpty($data['vs'])) {
            $this->validate('e2eReference');
        }

        if (!$this->isEmpty($data['submerchantId']) || !$this->isEmpty($data['location']) || !$this->isEmpty($data['city']) || !$this->isEmpty($data['alpha2CountryCode'])) {
            $this->validate('submerchantId', 'location', 'city', 'alpha2CountryCode');
        }

        return $data;
    }

    #[\Override]
    public function send(): CardTransactionResponse
    {
        return $this->sendData($this->getData());
    }

    /**
     * @throws \SoapFault for a fault other than a decline
     */
    #[\Override]
    public function sendData(mixed $data): CardTransactionResponse
    {
        $data = self::arrayData($data);

        if ($this->getTestMode()) {
            // test mode: an even card id is declined (02), an odd one approved (00)
            $status = self::isEven($data['referedCardId']) ? '02' : '00';

            return $this->response = new CardTransactionResponse(
                $this,
                ['transactionId' => $data['transactionId'], 'transactionStatus' => $status, 'transactionApproval' => '123'],
            );
        }

        $req = new \stdClass();
        $req->transactionId = $data['transactionId'];
        $req->referedCardId = $data['referedCardId'];
        $req->merchantId = $data['merchantId'];
        $req->terminalId = $data['terminalId'];
        $req->amount = $data['amount'];
        $req->parentTransactionId = $data['parentTransactionId'];
        $req->cc = $data['cc'];

        $transactionIdentificator = new \stdClass();
        if (!$this->isEmpty($data['vs']) && !$this->isEmpty($data['ss'])) {
            $symbols = new \stdClass();
            $symbols->variableSymbol = $data['vs'];
            $symbols->specificSymbol = $data['ss'];
            $transactionIdentificator->symbols = $symbols;
        } else {
            $transactionIdentificator->e2eReference = $data['e2eReference'];
        }
        $req->transactionIdentificator = $transactionIdentificator;

        if (!$this->isEmpty($data['submerchantId']) && !$this->isEmpty($data['location']) && !$this->isEmpty($data['city']) && !$this->isEmpty($data['alpha2CountryCode'])) {
            $ipspData = new \stdClass();
            $ipspData->submerchantId = $data['submerchantId'];
            $ipspData->location = $data['location'];
            $ipspData->city = $data['city'];
            $ipspData->alpha2CountryCode = $data['alpha2CountryCode'];
            $req->ipspData = $ipspData;
        }

        $request = new \stdClass();
        $request->req = $req;
        $request->transactionType = $data['transactionType'];

        try {
            $response = $this->call('doCardTransaction', $request);
        } catch (\SoapFault $fault) {
            // Tatra banka answers a charge it declines (for example an unknown card) with a SOAP-ENV:Receiver fault.
            // It is a regular answer, not a failure of the service, so it becomes an unsuccessful response.
            // Other faults (network, outage, invalid request) are rethrown.
            if ($fault->faultcode === 'SOAP-ENV:Receiver') {
                return $this->response = new CardTransactionResponse($this, [
                    'transactionId' => false,
                    'transactionStatus' => false,
                    'transactionApproval' => false,
                ]);
            }
            throw $fault;
        }

        return $this->response = new CardTransactionResponse($this, self::transactionResult($response));
    }

    private function isEmpty(mixed $value): bool
    {
        return in_array($value, [null, '', '0'], true);
    }
}
