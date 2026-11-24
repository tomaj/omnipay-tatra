<?php

declare(strict_types=1);

namespace Omnipay\ComfortPay\Message;

use Omnipay\ComfortPay\Exception\ComfortPaySoapFault;

/**
 * The registered cards whose card expires in a given month (getListOfExpired).
 */
final class ListOfExpiredRequest extends AbstractSoapRequest
{
    /**
     * A date in the expiry month, format YYYY-MM-DD (xsd:date). Only the month and the year are used;
     * the day is ignored.
     */
    public function getExpDate(): ?string
    {
        return $this->getStringParameter('expDate');
    }

    public function setExpDate(?string $value): static
    {
        return $this->setParameter('expDate', $value);
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function getData(): array
    {
        $this->validate('expDate');

        $data = parent::getData();
        $data['expDate'] = $this->getExpDate();

        return $data;
    }

    #[\Override]
    public function send(): ListOfExpiredResponse
    {
        return $this->sendData($this->getData());
    }

    /**
     * @throws ComfortPaySoapFault
     */
    #[\Override]
    public function sendData(mixed $data): ListOfExpiredResponse
    {
        $data = self::arrayData($data);

        if ($this->getTestMode()) {
            return $this->response = new ListOfExpiredResponse($this, [
                ['id' => 12345],
                ['id' => 12346],
            ]);
        }

        $request = new \stdClass();
        $request->expDate = $data['expDate'];

        $resultData = array_map(
            static fn(\SimpleXMLElement $element): array => ['id' => (string) $element],
            $this->callForList('getListOfExpired', $request),
        );

        return $this->response = new ListOfExpiredResponse($this, $resultData);
    }
}
