<?php

declare(strict_types=1);

namespace Omnipay\Core\Sign;

/**
 * HMAC-SHA256 with the 128-character hexadecimal key (64 bytes), lowercase hexadecimal output.
 */
final class HmacSign implements SignInterface
{
    #[\Override]
    public function sign(string $input, #[\SensitiveParameter] string $secret): string
    {
        return hash_hmac('sha256', $input, pack('H*', $secret));
    }
}
