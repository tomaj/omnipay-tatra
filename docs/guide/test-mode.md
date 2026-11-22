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
