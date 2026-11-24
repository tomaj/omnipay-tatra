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

## How do I find the order on the return URL?

By the `VS` query parameter: look the order up by it, then pass that order's `vs`, `amount` and `currency` to
`completePurchase()`. Never pass values from the query string as the expected ones. The [shop example](/examples/shop#the-return-url)
does exactly this.

## The customer reloaded the return URL. Is that a problem?

The reload is a correctly signed callback and verifies again. Change the order only on its first successful return,
like the [shop example](/examples/shop#the-return-url).

## completePurchase() rejects the simulator's AES or DES return with "incorect signature"

You set `ss`. payment-simulator signs its TatraPay AES and DES returns over `VS RES`, the library verifies
`VS SS RES`; leave `ss` empty for those key types in test mode. HMAC returns include `SS` on both sides. See
[Test mode](/guide/test-mode#payment-simulator).

## Is there a notification from the bank to my server?

Not for these gateways: the result arrives with the customer on the return URL. Set `rem` to get the result by
e-mail as well, and `aredir` to `true` so that the bank sends the customer back without waiting for a click. An order
whose customer never came back stays unpaid in your shop; check it against the e-mail or the bank's merchant portal.
