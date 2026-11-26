<?php

declare(strict_types=1);

namespace Omnipay\Core\Sign;

use Omnipay\Core\Exception\SignatureException;

interface SignInterface
{
    /**
     * @throws SignatureException when the signature cannot be computed
     */
    public function sign(string $input, #[\SensitiveParameter] string $secret): string;
}
