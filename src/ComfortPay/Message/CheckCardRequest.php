<?php

declare(strict_types=1);

namespace Omnipay\ComfortPay\Message;

use Omnipay\ComfortPay\CardStatus;

/**
 * Checks whether a registered card can still be charged (checkCard).
 */
final class CheckCardRequest extends AbstractSoapRequest
{
    public function getCardId(): ?string
    {
        return $this->getStringParameter('cardId');
    }

    public function setCardId(?string $value): static
    {
        return $this->setParameter('cardId', $value);
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function getData(): array
    {
        $this->validate('cardId');

        $data = parent::getData();
        $data['cardId'] = $this->getCardId();

        return $data;
    }

    #[\Override]
    public function send(): CheckCardResponse
    {
        return $this->sendData($this->getData());
    }

    /**
     * @throws \SoapFault
     */
    #[\Override]
    public function sendData(mixed $data): CheckCardResponse
    {
        $data = self::arrayData($data);

        if ($this->getTestMode()) {
            // test mode: an even card id is OK, an odd one FAIL
            $status = self::isEven($data['cardId']) ? CardStatus::Ok : CardStatus::Fail;

            return $this->response = new CheckCardResponse($this, (object) ['status' => $status->value]);
        }

        $request = new \stdClass();
        $request->idOfCard = $data['cardId'];

        return $this->response = new CheckCardResponse($this, $this->call('checkCard', $request));
    }
}
