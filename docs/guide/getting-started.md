---
title: Getting started
description: Install omnipay-tatra, redirect the customer to Tatra banka and verify the payment when they return.
---

# Getting started

## Requirements

- PHP 8.4 or newer with the `openssl`, `soap` and `simplexml` extensions
- an HTTP client adapter that Omnipay can discover, for example `php-http/guzzle7-adapter` (Guzzle 7)
- a merchant contract with Tatra banka: the merchant id (`MID`) and the shared secret (key) for each service

## Installation

```bash
composer require tomaj/omnipay-tatra php-http/guzzle7-adapter
```

The package registers three Omnipay gateways: `TatraPay`, `CardPay` and `ComfortPay`. Create them with
`Omnipay::create()` or with `new \Omnipay\TatraPay\Gateway()`.

## The payment flow

Every gateway works the same way:

1. **Purchase**: build a signed request and redirect the customer to the bank.
2. The customer pays (or not) and the bank redirects them back to your return URL (`rurl`) with the result and a
   signature in the query string.
3. **Complete purchase**: on the return URL, verify the signature and the variable symbol, then mark the order paid.

[How a payment works](./payment-flow) explains each step with a diagram.

## Redirect the customer

<<< @/snippets/tatrapay-purchase.php#snippet

`vs` (variable symbol, up to 10 digits) identifies the order. Keep it with the order: you need it to verify the return.

## Verify the return

<<< @/snippets/tatrapay-complete.php#snippet

`completePurchase()` throws `Omnipay\Common\Exception\InvalidRequestException` when the signature is wrong or the bank
returned a different variable symbol than the `vs` you pass. Only a response with `isSuccessful()` means the payment
went through.

::: warning Compare the amount
With HMAC keys the bank signs the amount, so a callback for a different amount fails the signature. AES and DES
signatures do not cover the amount: compare it with the order before you ship.
:::

## Run it against the simulator

You do not need a contract to try the flow. Start [payment-simulator](https://github.com/tomaj/payment-simulator)
locally, which accepts the requests signed with its public test keys and answers like the bank:

```bash
git clone https://github.com/tomaj/omnipay-tatra.git && cd omnipay-tatra
examples/ci/start-simulator.sh          # clones payment-simulator and serves it at http://127.0.0.1:8080
examples/ci/example.sh tatrapay         # composer install + run examples/tatrapay/run.php
```

The [TatraPay example](/examples/tatrapay) redirects to the simulator, follows each of the bank's answers back and
verifies it; this is its real output, compared in CI on every change:

<<< @/../examples/tatrapay/expected-output.txt{text}

## Next steps

- [Configuration](./configuration): every gateway parameter and how the key selects the algorithm
- [TatraPay](./tatrapay), [CardPay](./cardpay), [ComfortPay](./comfortpay): all operations of each service
- [Test mode](./test-mode): try the flow without the bank
- [Examples](/examples/): complete recipes, from a shop's return URL to a ComfortPay subscription
- [Error handling](./errors) and [Security](/security) before you go live
