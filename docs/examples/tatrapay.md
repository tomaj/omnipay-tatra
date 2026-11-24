---
title: TatraPay round trip
description: A complete TatraPay payment against payment-simulator - the signed redirect, each of the bank's answers verified by completePurchase(), and a forged and a foreign callback rejected.
---

# TatraPay round trip

The customer pays from Tatra banka internet banking. The example sends a TatraPay payment with an HMAC key to
[payment-simulator](/guide/test-mode#payment-simulator), follows each of the three answers the bank can give back to the
return URL and verifies it, then tries two attacks.

Source: [`examples/tatrapay/run.php`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/tatrapay/run.php) ·
run it with `examples/ci/example.sh tatrapay`.

## 1. Configure the gateway

<<< @/../examples/tatrapay/run.php#config

`testMode` sends the customer to `testHost` instead of `https://moja.tatrabanka.sk`. The simulator's HMAC test key is
128 times `1`; with your contract you get your own `mid` and key.

## 2. Redirect the customer

<<< @/../examples/tatrapay/run.php#purchase

`purchase()` calls nobody: it builds the signed URL. In a web request call `$response->redirect()`, which sends a
`302` and exits. The example prints the fields instead:

```text
redirect: http://127.0.0.1:8080/payment/tatrapay-hmac
  PT        TatraPay
  MID       9999
  AMT       24.90
  CURR      978
  ...
  HMAC      64 hex characters
```

`CURR` is the numeric ISO 4217 code. Besides these fields the URL carries `TIMESTAMP` (the current UTC time, signed)
and the empty optional fields.

## 3. Verify the return

The simulator answers like the bank: `continue ok` (`RES=OK`), `continue fail` (`RES=FAIL`) and `continue timeout`
(`RES=TOUT`), each a signed link to `rurl`. On the return URL the shop calls `completePurchase()` with the order it
expects:

<<< @/../examples/tatrapay/run.php#complete

In a web application `Omnipay::create('TatraPay')` reads the current request; the example passes the customer's
request to the gateway's constructor instead, because one script plays both sides.

## 4. Attacks that fail

<<< @/../examples/tatrapay/run.php#forged

- An edited result fails the signature: `RES` is part of the signed string.
- A correctly signed answer for order `2026001` is not accepted for order `2026002`: the returned `VS` must be the
  one you expect.

## Output

<<< @/../examples/tatrapay/expected-output.txt{text}

## What it tests

- the simulator accepts the request signature (`Sign OK`), so the fields and their order are the bank's;
- `isSuccessful()` for `OK`, `isPending()` for `TOUT`, neither for `FAIL`;
- `InvalidRequestException` for an edited result and for another order's callback.

Next: [CardPay with HMAC and AES](./cardpay), or the same flow inside a real [shop](./shop).
