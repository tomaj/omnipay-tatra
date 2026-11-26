---
title: Security
description: What omnipay-tatra verifies for you, what you still have to check before you ship an order, how to keep the keys safe, and how to report a vulnerability.
---

# Security

A payment integration is attacked where the customer's browser carries data: the redirect to the bank and, above
all, the return to your shop. This page lists what the library does about it and what is left to you.

## Reporting a vulnerability

Do **not** open a public issue. Report it privately through
[GitHub private vulnerability reporting](https://github.com/tomaj/omnipay-tatra/security/advisories/new) or by e-mail,
as described in [SECURITY.md](https://github.com/tomaj/omnipay-tatra/blob/master/SECURITY.md), which also lists the
supported versions. Fixes are published as [GitHub Security Advisories](https://github.com/tomaj/omnipay-tatra/security/advisories).

## What the library verifies

| Threat | Protection |
|---|---|
| the customer edits the return URL (`RES=FAIL` to `RES=OK`) | the result is signed; `completePurchase()` throws `InvalidRequestException` ([example](/examples/tatrapay)) |
| a valid callback of a cheap order is replayed for an expensive one | the returned `VS` must equal the `vs` you pass; the expected VS is part of the signed string |
| a callback for a different amount (HMAC keys) | the signed string is built from **your** `amount` and `currency` |
| guessing the signature byte by byte | signatures are compared with `hash_equals()`; a missing or array-valued `HMAC`/`SIGN` never matches |
| a forged capture/refund answer | the XML answer of `cancelAuthorize()` is verified against the `HMAC` in the `Authorization` header |
| a man in the middle on the ComfortPay web service | TLS with certificate and host name verification, against the system CA store or your `caFile` |
| a customer name the bank rejects after the redirect | `name` is validated before the redirect |

## What you still check

- **The amount with AES and DES keys.** Their return signatures cover only the VS and the result (and the approval
  code for CardPay). Compare the order's amount yourself, or better, ask Tatra banka for an HMAC key.
- **That an order is paid once.** The customer can reload the return URL; it verifies again. Change the order only on
  its first successful return ([shop example](/examples/shop#the-return-url)).
- **That `TOUT` is not `OK`.** A TatraPay timeout is `isPending()`, not paid.
- **That a `FAIL` of `cancelAuthorize()` is not proof.** An unsigned `FAIL` answer is accepted; treat it as "not done".
- **Test mode is off in production.** With `testMode` the redirect goes to a simulator that signs with public keys.

## Keys and certificates

- Load `sharedSecret`, `certPath` and `certPass` from the environment or a secret store; never commit them. The
  [shop example](/examples/shop) reads `TATRA_SHARED_SECRET` and falls back to the public test key only for the
  simulator.
- Restrict the ComfortPay client certificate file to the user your PHP runs as.
- Never turn off TLS verification for the web service. If the system CA store cannot verify the bank, pass a CA bundle
  with `caFile` or your own `streamContext` ([TLS and proxy](./guide/comfortpay#tls-and-proxy)).
- A leaked key lets anyone sign callbacks for your shop: ask Tatra banka for a new one at once.

## Secrets in error reports

The shared secret and the ComfortPay passwords are marked `#[\SensitiveParameter]` wherever they are a function
argument: `setSharedSecret()`, `setCertPass()`, `setProxyPassword()`, the signers' `sign()` and `KeyType::fromSecret()`.
When an exception is thrown while one of these calls is on the stack, PHP shows `Object(SensitiveParameterValue)`
instead of the value in `getTraceAsString()` and in error reports (Tracy, Sentry, Monolog).

The attribute does not cover values passed inside an array or held by an object: `initialize(['sharedSecret' => ...])`,
`purchase([...])` or the options this library passes to `SoapClient` appear in a trace as `Array`, and the gateway or a
request passed through your own code as `Object(...)`; tools that dump arguments in full (Tracy's BlueScreen, a
`print_r()` of `getTrace()`) show the secrets inside them. Set `zend.exception_ignore_args = On` in production (the default of `php.ini-production`):
traces then contain no arguments at all.

## Known limitations

- The AES signature uses AES-128 and the first half of the 64-character key, as it has since 2017; whether Tatra banka
  expects AES-256 is not confirmed ([#34](https://github.com/tomaj/omnipay-tatra/issues/34)). See
  [Signatures](./guide/signatures#aes).
- DES is a legacy cipher, available in OpenSSL 3 only through the legacy provider.
