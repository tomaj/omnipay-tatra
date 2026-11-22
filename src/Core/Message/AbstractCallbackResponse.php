<?php

declare(strict_types=1);

namespace Omnipay\Core\Message;

use Omnipay\Common\Message\AbstractResponse;

/**
 * The verified parameters the bank sent back with the customer. Extended by the package's complete* responses.
 */
abstract class AbstractCallbackResponse extends AbstractResponse
{
    public const string SUCCESS = 'OK';

    #[\Override]
    public function isSuccessful(): bool
    {
        return static::SUCCESS === $this->getRes();
    }

    /**
     * The result: OK, FAIL, or TOUT (TatraPay timeout).
     */
    public function getRes(): ?string
    {
        return $this->getDataString('RES');
    }

    /**
     * The variable symbol, verified to be the one passed to the request.
     */
    public function getVs(): ?string
    {
        return $this->getDataString('VS');
    }

    protected function getDataString(string $key): ?string
    {
        if (!is_array($this->data) || !isset($this->data[$key])) {
            return null;
        }

        $value = $this->data[$key];

        return is_string($value) ? $value : null;
    }
}
