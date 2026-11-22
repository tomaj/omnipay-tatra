<?php

declare(strict_types=1);

namespace Omnipay\Core\Sign;

use Omnipay\Core\Exception\UnsupportedKeyException;

/**
 * The signature algorithm, selected by the length of the shared secret Tatra banka issued.
 */
enum KeyType: int
{
    /** 8 characters */
    case Des = 8;
    /** 64 hexadecimal characters */
    case Aes = 64;
    /** 128 hexadecimal characters */
    case Hmac = 128;

    /**
     * @throws UnsupportedKeyException
     */
    public static function fromSecret(string $secret): self
    {
        return self::tryFrom(strlen($secret)) ?? throw new UnsupportedKeyException(
            'Unknown key length ' . strlen($secret) . ': the shared secret must have 8 (DES), 64 (AES) or 128 (HMAC) characters',
        );
    }

    public function signer(): SignInterface
    {
        return match ($this) {
            self::Des => new DesSign(),
            self::Aes => new Aes256Sign(),
            self::Hmac => new HmacSign(),
        };
    }
}
