---
title: Examples
description: Six complete, runnable omnipay-tatra recipes - TatraPay, CardPay with HMAC and AES, pre-authorization with capture and refund, a ComfortPay subscription, a shop's return URL and signatures checked with openssl.
---

# Examples

Every example is a runnable project in
[`examples/`](https://github.com/tomaj/omnipay-tatra/tree/master/examples) of the repository. The code on these pages
is included from those files, and the [Examples workflow](https://github.com/tomaj/omnipay-tatra/blob/master/.github/workflows/examples.yml)
runs each of them on every pull request against the pull request's code and compares the output with its
`expected-output.txt`. Nothing here talks to Tatra banka or moves money: the payments go to a local
[payment-simulator](/guide/test-mode#payment-simulator), the ComfortPay web service answers in test mode.

| Example | Shows | Runs against |
|---|---|---|
| [TatraPay round trip](./tatrapay) | `purchase()`, each of the bank's answers verified by `completePurchase()`, a forged and a foreign callback rejected | simulator |
| [CardPay with HMAC and AES](./cardpay) | the same code with both key types, customer name validation | simulator |
| [Pre-authorization and capture](./cardpay-preauthorization) | `authorize()` with 3-D Secure data, `completeAuthorize()`, capture, release and refund with `cancelAuthorize()` | simulator |
| [ComfortPay subscription](./comfortpay-recurring) | card registration, a recurring `charge()`, `checkTransaction()`, `checkCard()`, card expiration, `unRegisterCard()` | simulator + test mode |
| [Shop with a return URL](./shop) | a shop on the PHP built-in server: checkout, return URL handler, reload, edited and replayed callbacks | simulator |
| [Signatures and openssl](./signatures) | the signed string of each key type, checked against the `openssl` command line | offline |

## Run them

```bash
git clone https://github.com/tomaj/omnipay-tatra.git
cd omnipay-tatra
examples/ci/example.sh tatrapay      # or cardpay, cardpay-preauthorization, comfortpay-recurring, shop, signatures
```

[`examples/ci/example.sh`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/ci/example.sh) installs the
example with Composer (its `composer.json` takes `tomaj/omnipay-tatra` from the repository root through a path
repository), starts payment-simulator with
[`examples/ci/start-simulator.sh`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/ci/start-simulator.sh)
when it is not running yet, and runs the example's `e2e.sh`, which prints `ok: <example>` when the output matches.
Requirements: PHP 8.4+ with `openssl`, `soap` and `iconv`, Composer, git and curl. Set `SIMULATOR_URL` to use a
simulator that runs elsewhere, for example in Docker.

To run an example by hand, `cd examples/tatrapay && composer install && php run.php`.

The examples share one helper, [`examples/_support/simulator.php`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/_support/simulator.php),
which plays the customer at the simulator: it opens the payment page, reads the verdict and the three signed links
back to the shop.

<<< @/../examples/_support/simulator.php#pay
