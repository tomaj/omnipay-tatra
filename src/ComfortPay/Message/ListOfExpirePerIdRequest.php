<?php

declare(strict_types=1);

namespace Omnipay\ComfortPay\Message;

use Omnipay\ComfortPay\Exception\ComfortPaySoapFault;

/**
 * The expiration (MMYY) of the given registered cards (getListOfExpPerId), at most 1000 ids.
 */
final class ListOfExpirePerIdRequest extends AbstractSoapRequest
{
    /**
     * @return list<string>|null
     */
    public function getCardIds(): ?array
    {
        $value = $this->getParameter('cardIds');
        if (!is_array($value)) {
            return null;
        }

        return array_values(array_map(static fn(mixed $id): string => is_scalar($id) ? (string) $id : '', $value));
    }

    /**
     * @param array<int|string>|null $value
     */
    public function setCardIds(?array $value): static
    {
        return $this->setParameter('cardIds', $value);
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function getData(): array
    {
        $this->validate('cardIds');

        $data = parent::getData();
        $data['cardIds'] = $this->getCardIds();

        return $data;
    }

    #[\Override]
    public function send(): ListOfExpirePerIdResponse
    {
        return $this->sendData($this->getData());
    }

    /**
     * @throws ComfortPaySoapFault
     */
    #[\Override]
    public function sendData(mixed $data): ListOfExpirePerIdResponse
    {
        $data = self::arrayData($data);
        /** @var list<string> $cardIds */
        $cardIds = $data['cardIds'] ?? [];

        if ($this->getTestMode()) {
            return $this->response = new ListOfExpirePerIdResponse($this, array_map(
                static fn(string $id): array => ['id' => $id, 'date' => '0199'],
                $cardIds,
            ));
        }

        $request = new \stdClass();
        $request->listOfIdCards = $cardIds;

        $resultData = array_map(
            static fn(\SimpleXMLElement $element): array => [
                'id' => (string) $element->idOfCard,
                'date' => (string) $element->expiration,
            ],
            $this->callForList('getListOfExpPerId', $request),
        );

        return $this->response = new ListOfExpirePerIdResponse($this, $resultData);
    }
}
