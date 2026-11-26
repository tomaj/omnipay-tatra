<?php

declare(strict_types=1);

namespace Omnipay\Core\Sign;

use Omnipay\Core\Exception\SignatureException;

/**
 * Decodes a hexadecimal shared secret without passing an invalid one to pack(), whose warning would put the secret into
 * the trace of an error handler that turns warnings into exceptions.
 *
 * @internal
 */
final class HexKey
{
    /**
     * @throws SignatureException when the secret contains a non-hexadecimal character
     */
    public static function decode(#[\SensitiveParameter] string $secret): string
    {
        if (strspn($secret, '0123456789abcdefABCDEF') !== strlen($secret)) {
            throw new SignatureException('The shared secret must be hexadecimal');
        }

        return pack('H*', $secret);
    }
}
