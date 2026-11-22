<?php

declare(strict_types=1);

namespace Omnipay\Core\Message;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Core\Exception\UnsupportedKeyException;
use Omnipay\Core\Sign\KeyType;

/**
 * Parameters shared by the requests of all Tatra banka gateways. Extended by the package's request classes.
 */
abstract class AbstractRequest extends \Omnipay\Common\Message\AbstractRequest
{
    public const string DEFAULT_TEST_HOST = 'https://platby.tomaj.sk';

    public function getMid(): ?string
    {
        return $this->getStringParameter('mid');
    }

    public function setMid(?string $value): static
    {
        return $this->setParameter('mid', $value);
    }

    public function getSharedSecret(): ?string
    {
        return $this->getStringParameter('sharedSecret');
    }

    public function setSharedSecret(?string $value): static
    {
        return $this->setParameter('sharedSecret', $value);
    }

    public function getTestHost(): ?string
    {
        return $this->getStringParameter('testHost');
    }

    public function setTestHost(?string $value): static
    {
        return $this->setParameter('testHost', $value);
    }

    public function getVs(): ?string
    {
        return $this->getStringParameter('vs');
    }

    public function setVs(?string $value): static
    {
        return $this->setParameter('vs', $value);
    }

    public function getCs(): ?string
    {
        return $this->getStringParameter('cs');
    }

    public function setCs(?string $value): static
    {
        return $this->setParameter('cs', $value);
    }

    public function getSs(): ?string
    {
        return $this->getStringParameter('ss');
    }

    public function setSs(?string $value): static
    {
        return $this->setParameter('ss', $value);
    }

    public function getDesc(): ?string
    {
        return $this->getStringParameter('desc');
    }

    public function setDesc(?string $value): static
    {
        return $this->setParameter('desc', $value);
    }

    public function getRsms(): ?string
    {
        return $this->getStringParameter('rsms');
    }

    public function setRsms(?string $value): static
    {
        return $this->setParameter('rsms', $value);
    }

    /**
     * 1 when the bank redirects the customer back automatically, 0 when not, null when not set.
     */
    public function getAredir(): ?int
    {
        $value = $this->getParameter('aredir');

        return $value !== null ? (int) (bool) $value : null;
    }

    public function setAredir(bool|int|string|null $value): static
    {
        return $this->setParameter('aredir', $value);
    }

    public function getLang(): ?string
    {
        return $this->getStringParameter('lang');
    }

    public function setLang(?string $value): static
    {
        return $this->setParameter('lang', $value);
    }

    public function getRem(): ?string
    {
        return $this->getStringParameter('rem');
    }

    public function setRem(?string $value): static
    {
        return $this->setParameter('rem', $value);
    }

    /**
     * The request time in UTC, format ddmmYYYYHHMMSS (signed in HMAC mode).
     */
    public function getTimestamp(): ?string
    {
        return $this->getStringParameter('timestamp');
    }

    public function setTimestamp(?string $value): static
    {
        return $this->setParameter('timestamp', $value);
    }

    public function getRurl(): ?string
    {
        return $this->getStringParameter('rurl');
    }

    public function setRurl(?string $value): static
    {
        return $this->setParameter('rurl', $value);
    }

    /**
     * Reads a parameter that the bank handles as a string (ids, symbols, urls, secrets).
     */
    protected function getStringParameter(string $key): ?string
    {
        $value = $this->getParameter($key);
        if (is_string($value)) {
            return $value;
        }
        if (is_int($value)) {
            return (string) $value;
        }

        return null;
    }

    /**
     * @throws UnsupportedKeyException
     */
    protected function getKeyType(): KeyType
    {
        return KeyType::fromSecret($this->getSharedSecret() ?? '');
    }

    /**
     * @throws UnsupportedKeyException
     */
    protected function sign(string $input): string
    {
        return $this->getKeyType()->signer()->sign($input, $this->getSharedSecret() ?? '');
    }

    /**
     * The ISO 4217 numeric code of the currency (978 for EUR).
     *
     * @throws InvalidRequestException when no currency is set
     */
    protected function getCurrencyNumericCode(): string
    {
        $this->validate('currency');
        $currency = $this->getCurrency();
        if ($currency === '') {
            throw new InvalidRequestException('The currency parameter is required');
        }

        return (string) new ISOCurrencies()->numericCodeFor(new Currency($currency));
    }

    /**
     * A query parameter of the request the bank redirected the customer with; '' when it is missing or not a string.
     */
    protected function getQueryParameter(string $name): string
    {
        $value = $this->httpRequest->query->all()[$name] ?? null;

        return is_string($value) ? $value : '';
    }

    /**
     * The host of the test-mode endpoints, without a trailing slash.
     */
    protected function getTestEndpointHost(): string
    {
        $testHost = $this->getTestHost();

        return $testHost !== null && $testHost !== '' ? $testHost : self::DEFAULT_TEST_HOST;
    }

    /**
     * Whether a numeric id is even; the test mode of the SOAP requests answers by the parity of the id.
     */
    protected static function isEven(mixed $id): bool
    {
        return (int) (is_scalar($id) ? $id : 0) % 2 === 0;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function arrayData(mixed $data): array
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException('The request data must be the array returned by getData()');
        }

        /** @var array<string, mixed> $data */
        return $data;
    }
}
