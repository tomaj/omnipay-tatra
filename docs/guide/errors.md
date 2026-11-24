---
title: Error handling
description: Every exception omnipay-tatra throws, when it is thrown and how to handle it - invalid parameters, forged callbacks, unsupported keys, bank errors and ComfortPay SOAP faults.
---

# Error handling

The package separates three kinds of failure:

- **the bank said no**: a normal, unsuccessful response (`RES=FAIL`, a declined ComfortPay charge, `res` `FAIL` of a
  capture). Nothing is thrown; check `isSuccessful()`.
- **the input is not trustworthy or not valid**: Omnipay's `InvalidRequestException` (a parameter is missing or invalid,
  a callback is forged or belongs to another order) and `InvalidResponseException` (the bank's server-to-server answer
  is not signed correctly or not XML).
- **configuration or communication problem**: an exception implementing `Omnipay\Core\Exception\TatraException`.

## Every exception

| Exception | Extends | Thrown by | When |
|---|---|---|---|
| `Omnipay\Common\Exception\InvalidRequestException` | `Exception` | every request | a required parameter is missing (`The vs parameter is required`), `name` has characters the bank rejects, no `currency` |
| | | `completePurchase()`, `completeAuthorize()` | `Variable symbol mismatch`: the bank returned another `VS` than the `vs` you pass |
| | | `completePurchase()`, `completeAuthorize()` | `incorect signature` (CardPay HMAC: `incorrect signature for data: '...'`): the `HMAC`/`SIGN` does not match |
| `Omnipay\Common\Exception\InvalidResponseException` | `Exception` | `cancelAuthorize()` | `Incorrect signature` of the XML answer, or the answer is not XML |
| | | `listOfExpired()`, `listOfExpirePerId()` | the web service answer is not XML |
| `Omnipay\Core\Exception\UnsupportedKeyException` | `InvalidArgumentException` | every signed request | the shared secret is not 8, 64 or 128 characters long, or a DES key for ComfortPay |
| `Omnipay\Core\Exception\SignatureException` | `RuntimeException` | every signed request | OpenSSL cannot compute the signature, typically DES without the legacy provider |
| `Omnipay\Core\Exception\GatewayException` | `RuntimeException` | `cancelAuthorize()` | the bank answered with an HTTP status other than 200 |
| `Omnipay\Core\Exception\InvalidParameterException` | `UnexpectedValueException` | `cancelAuthorize()`, `charge()` | `txn` other than `CPA`, `SPA`, `CB`, or an unknown `transactionType` |
| `Omnipay\ComfortPay\Exception\ComfortPaySoapFault` | `SoapFault` | every ComfortPay web service request | a SOAP fault, except a declined `charge()` (see [below](#comfortpay-web-service)) |

All of `UnsupportedKeyException`, `SignatureException`, `GatewayException`, `InvalidParameterException` and
`ComfortPaySoapFault` implement `Omnipay\Core\Exception\TatraException`, so one `catch` handles every problem that is
not about the input. The `InvalidRequestException` of the [vs check](/upgrade#tatrapay-completepurchase-requires-vs)
and of the signature check is the one you must treat as a possible attack.

## On the return URL

```php
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Core\Exception\TatraException;

try {
    $response = $gateway->completePurchase(['vs' => $order->vs, 'amount' => $order->amount, 'currency' => 'EUR'])->send();
} catch (InvalidRequestException $e) {
    // forged, edited or foreign callback: change nothing, log it, answer 400
    $status = 'rejected';
} catch (TatraException $e) {
    // wrong key configured, OpenSSL without DES: a bug on your side, alert
    $status = 'error';
}
```

Do not show the exception message to the customer; the CardPay message contains the signed data. Do not retry: the
same query string fails the same way.

`RES=FAIL` is not an exception: `isSuccessful()` is `false` and `getRes()` tells you why. For TatraPay, `isPending()`
is `true` for `RES=TOUT`: the bank has not decided yet and e-mails the result to `rem`; keep the order open.

## Capture, release and refund

`cancelAuthorize()` makes a server-to-server request:

| Outcome | What you get |
|---|---|
| the bank did it | `isSuccessful()` `true`, `getRes()` `OK` |
| the bank refused | `isSuccessful()` `false`, `getRes()` `FAIL`, `getErrorCode()` (also `getCode()`) is the bank's error code |
| HTTP status other than 200 | `GatewayException` with the status in the message |
| wrong `HMAC` in the `Authorization` header, or no XML | `InvalidResponseException` |
| network error | Omnipay's `Omnipay\Common\Http\Exception\NetworkException` (connection) or `RequestException` (anything else the HTTP client throws) |

A `FAIL` answer without an `Authorization` header is accepted as it is (the bank does not sign every error answer):
treat a `FAIL` as "not done", never as proof that something was done.

<<< @/../examples/cardpay-preauthorization/run.php#wrong-key

## ComfortPay web service

Tatra banka declines a charge with a SOAP fault (`SOAP-ENV:Receiver`) that carries an error code. `charge()` turns that
fault into an unsuccessful `CardTransactionResponse` with `getErrorCode()`; every other fault, and every fault of the
other web service requests, is thrown as `ComfortPaySoapFault` with `getErrorCode(): ?int` and `isReceiverFault(): bool`.

```php
use Omnipay\ComfortPay\Exception\ComfortPaySoapFault;

try {
    $charge = $comfortPay->charge($chargeParameters)->send();
} catch (ComfortPaySoapFault $fault) {
    // the service failed: retry later with the same transactionId, or check it with checkTransaction()
    $code = $fault->getErrorCode();
}
```

| `CardTransactionResponse` | Meaning | Do |
|---|---|---|
| `isSuccessful()` | `transactionStatus` `00` | done |
| `isPending()` | error code `50001` | do **not** charge again: ask `checkTransaction()` later |
| `getErrorCode()` not `null` | declined by the bank, see the [error codes](./comfortpay#declines-and-errors) | do not retry `50051` (unknown card) or `50054` (duplicate) |
| `getTransactionStatus()` other than `00` | declined by the card network (`05`, `51`, `54` ...) | ask the customer for another card |

## Before the redirect

`purchase()` and `authorize()` validate their input before anything leaves your server: a missing `vs`, `rurl`,
`currency` (or `name` and `ipc` for CardPay) and a `name` with diacritics throw `InvalidRequestException`. Handle them
as a bug in your checkout, not as a payment failure.
