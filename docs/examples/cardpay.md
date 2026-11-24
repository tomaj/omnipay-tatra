---
title: CardPay with HMAC and AES
description: CardPay card payments against payment-simulator with an HMAC and an AES key - the same purchase() and completePurchase() code, and the customer name validation.
---

# CardPay with HMAC and AES

CardPay takes the card on Tatra banka's page. The example runs the same code twice: with a 128-character HMAC key
(what Tatra banka issues today) and with a 64-character AES key (older contracts). The length of `sharedSecret` selects
the algorithm, the endpoint and the signed fields; your code does not change.

Source: [`examples/cardpay/run.php`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/cardpay/run.php) ·
run it with `examples/ci/example.sh cardpay`.

## The payment

<<< @/../examples/cardpay/run.php#purchase

CardPay needs two fields TatraPay does not: `name`, the cardholder's name, and `ipc`, the customer's IP address
(taken from `REMOTE_ADDR` when you leave it out).

| | HMAC key | AES key |
|---|---|---|
| endpoint | `.../cardpay` (`/payment/cardpay-hmac` in test mode) | `.../e-commerce.jsp` (`/payment/cardpay-aes256`) |
| request signed in | `HMAC`, with `TIMESTAMP` | `SIGN` |
| return signed over | `AMT CURR VS RES AC TRES CC RC TID TIMESTAMP` | `VS RES AC` |
| amount verified on return | yes | **no**: compare it yourself |

## The return

<<< @/../examples/cardpay/run.php#complete

`amount` and `currency` are part of the signed string with HMAC keys only; passing them always does no harm.

## Customer names

The bank accepts only letters without diacritics, digits, space and `. - _ @` in `NAME`. The library checks the name
before the redirect, so the customer never sees the bank's error page:

<<< @/../examples/cardpay/run.php#name

## Output

<<< @/../examples/cardpay/expected-output.txt{text}

## What it tests

- both key types produce requests the simulator accepts, at the right endpoint, in the right field;
- both kinds of return are verified by the same `completePurchase()` call;
- a name with diacritics is rejected with `InvalidRequestException`, and the transliterated one is valid.
