<?php

declare(strict_types=1);

namespace Omnipay\Core\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * A response that redirects the customer to the bank with a signed GET request. Extended by the package's purchase
 * and authorize responses.
 */
abstract class AbstractRedirectResponse extends AbstractResponse implements RedirectResponseInterface
{
    #[\Override]
    public function isSuccessful(): bool
    {
        return false;
    }

    #[\Override]
    public function isRedirect(): bool
    {
        return true;
    }

    #[\Override]
    public function getRedirectUrl(): string
    {
        return $this->getEndpoint() . '?' . http_build_query($this->getRedirectData());
    }

    #[\Override]
    public function getRedirectMethod(): string
    {
        return 'GET';
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function getRedirectData(): array
    {
        /** @var array<string, mixed> */
        return is_array($this->data) ? $this->data : [];
    }

    abstract protected function getEndpoint(): string;
}
