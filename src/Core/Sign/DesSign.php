<?php

declare(strict_types=1);

namespace Omnipay\Core\Sign;

use Omnipay\Core\Exception\SignatureException;

/**
 * DES with the 8-character key: the first 8 bytes of DES-ECB(SHA-1(input)), uppercase hexadecimal (16 characters).
 *
 * OpenSSL 3 only offers des-ecb through its legacy provider.
 */
final class DesSign implements SignInterface
{
    private const string CIPHER = 'des-ecb';

    #[\Override]
    public function sign(string $input, string $secret): string
    {
        // ECB mode uses no initialization vector
        $encrypted = openssl_encrypt(sha1($input, true), self::CIPHER, $secret, OPENSSL_RAW_DATA);
        if ($encrypted === false) {
            throw new SignatureException('Cannot sign with cipher ' . self::CIPHER . ', is it supported by the OpenSSL build?');
        }

        return strtoupper(substr(bin2hex($encrypted), 0, 16));
    }
}
