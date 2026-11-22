<?php

declare(strict_types=1);

namespace Omnipay\Core\Sign;

final class Signature
{
    /**
     * Compares a computed signature with the one received from the bank in constant time.
     * A missing or non-string received value (e.g. HMAC[]=... in the query) never matches.
     */
    public static function equals(string $expected, mixed $received): bool
    {
        return is_string($received) && hash_equals($expected, $received);
    }
}
