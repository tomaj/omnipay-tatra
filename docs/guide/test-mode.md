---
title: Test mode
description: Send TatraPay, CardPay and ComfortPay requests to a simulator instead of Tatra banka.
---

# Test mode

With `testMode` the redirecting requests go to a simulator, and the ComfortPay web service requests answer locally
without calling the bank. Never enable it in production.

```php
$gateway->setTestMode(true);
$gateway->setTestHost('http://localhost:4444'); // optional, default https://platby.tomaj.sk
```

## Endpoints

| Request | Production | Test mode |
|---|---|---|
| TatraPay HMAC | `https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/tatrapay` | `{testHost}/payment/tatrapay-hmac` |
| TatraPay AES / DES | `https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp` | `{testHost}/payment/tatrapay-aes256` / `tatrapay-des` |
| CardPay HMAC | `https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/cardpay` | `{testHost}/payment/cardpay-hmac` |
| CardPay AES / DES | `https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp` | `{testHost}/payment/cardpay-aes256` / `cardpay-des` |
| CardPay `authorize()` | `https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/cardpay` | `{testHost}/payment/cardpay-authorize-hmac` |
| CardPay `cancelAuthorize()` | `https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/txn_process.jsp` | `{testHost}/payment/cardpay-cancel-purchase` |
| ComfortPay HMAC / AES | as CardPay | `{testHost}/payment/comfortpay-hmac` / `comfortpay-aes256` |
| ComfortPay web service | `https://comfortpay.tatrabanka.sk:8011/services/Teleplatba_1_0` | no request, see below |

`https://platby.tomaj.sk` runs [payment-simulator](https://github.com/tomaj/payment-simulator); run it yourself and
point `testHost` at it to test the whole flow locally.

## payment-simulator

The simulator accepts the same signed requests as the bank, signed with its public test keys, and shows a page with
the verdict (**Sign OK**, **Wrong sign** or **Missing parameters**) and three links back to your `rurl`: a successful
(`RES=OK`), a failed (`FAIL`) and a timed-out (`TOUT`) payment, signed like the bank signs them.

| Key type | Test key |
|---|---|
| HMAC | `str_repeat('1', 128)` |
| AES | `str_repeat('1', 64)` |
| DES | `'11111111'` |

Any `mid` works. Run it locally, without Docker:

```bash
examples/ci/start-simulator.sh   # in a checkout of omnipay-tatra: http://127.0.0.1:8080
```

The script clones payment-simulator into a temporary directory (or uses `PAYMENT_SIMULATOR_DIR`), installs it and
serves it with the PHP built-in server at `SIMULATOR_URL`. Every [example](/examples/) runs against it; the
[simulator's README](https://github.com/tomaj/payment-simulator#readme) covers Docker and every endpoint.

<<< @/../examples/tatrapay/run.php#config

What the simulator does not do:

- it sends no `TID` on TatraPay and CardPay returns; `getTransactionReference()` is only set by `completeAuthorize()`
  with `tpay` `Y`;
- its TatraPay AES and DES returns are signed over `VS RES`, while the library verifies `VS SS RES`: leave `ss` empty
  with AES and DES keys in test mode (HMAC returns include `SS` on both sides);
- it does not answer the ComfortPay web service; test mode answers those requests locally, see below.

## ComfortPay web service in test mode

The SOAP requests return fixed answers by the parity of the id, so both branches of your code can be exercised:

| Request | Even id | Odd id |
|---|---|---|
| `charge()` (`referedCardId`) | declined, status `02` | approved, status `00` |
| `checkTransaction()` (`transactionId`) | declined, status `02` | approved, status `00` |
| `checkCard()` (`cardId`) | `OK` | `FAIL` |
| `unRegisterCard()` (`cardId`) | not removed | removed |

`listOfExpired()` returns the cards `12345` and `12346`; `listOfExpirePerId()` returns every requested card with the
expiration `0199`.
