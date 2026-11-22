---
title: FAQ
description: Frequently asked questions about omnipay-tatra and Tatra banka's TatraPay, CardPay and ComfortPay.
---

# FAQ

## Which key do I have?

Count the characters of the shared secret Tatra banka gave you: 128 hexadecimal characters is HMAC, 64 is AES, 8 is
DES. New contracts get HMAC keys. See [Configuration](/guide/configuration#the-key-selects-the-algorithm).

## completePurchase() throws "Variable symbol mismatch"

You passed a different `vs` than the one the bank returned. Pass the variable symbol of the order you are completing;
take it from your session or from the order looked up by the `VS` query parameter, and keep leading zeros (`0123` and
`123` are different).

## completePurchase() throws "incorect signature"

The callback was changed, or you verify it with other values than you signed: use the same key, `vs`, `ss`, `cs` and,
for HMAC keys, the same `amount` and `currency` as in `purchase()`.

## DES signatures fail with "Cannot sign with cipher des-ecb"

OpenSSL 3 disabled DES. Enable the legacy provider in `openssl.cnf`, or better, ask Tatra banka for an HMAC key.

## The bank shows an error for the customer name

`name` may contain only letters without diacritics, digits, space and `. - _ @`; 5.0 rejects other names before the
redirect. Transliterate the name first.

## ComfortPay fails with an SSL certificate error

The web service certificate is verified against the system CA store. Pass a CA bundle with `caFile` or your own
`streamContext` ([TLS and proxy](/guide/comfortpay#tls-and-proxy)); do not turn the verification off.

## How do I test without the bank?

Use [test mode](/guide/test-mode), optionally with your own [payment-simulator](https://github.com/tomaj/payment-simulator)
as `testHost`.

## Can I use it without Omnipay::create()?

Yes: `new \Omnipay\TatraPay\Gateway($httpClient, $httpRequest)`. The callbacks read the query string from
`$httpRequest`, so pass the request of the current page (`Request::createFromGlobals()`).
