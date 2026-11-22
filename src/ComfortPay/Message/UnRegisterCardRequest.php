<?php

declare(strict_types=1);

namespace Omnipay\ComfortPay\Message;

use Omnipay\ComfortPay\Exception\ComfortPaySoapFault;

/**
 * Removes a registered card (unRegisterCard).
 */
final class UnRegisterCardRequest extends AbstractSoapRequest
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
    public function send(): UnRegisterCardResponse
    {
        return $this->sendData($this->getData());
    }

    /**
     * @throws ComfortPaySoapFault when the bank refuses to remove the card
     */
    #[\Override]
    public function sendData(mixed $data): UnRegisterCardResponse
    {
        $data = self::arrayData($data);

        if ($this->getTestMode()) {
            // test mode: an odd card id is removed, an even one is not
            return $this->response = new UnRegisterCardResponse($this, !self::isEven($data['cardId']));
        }

        $request = new \stdClass();
        $request->idOfCard = $data['cardId'];

        return $this->response = new UnRegisterCardResponse($this, $this->call('unRegisterCard', $request));
    }
}
