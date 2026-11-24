---
title: CardPay
description: CardPay card payments with omnipay-tatra - purchase, pre-authorization with 3-D Secure data, capture, reversal and refund.
---

# CardPay

CardPay takes card payments on Tatra banka's page. Besides a plain payment it supports a pre-authorization that you
capture, reverse or refund later with a server-to-server request. `authorize()` and `cancelAuthorize()` need an HMAC
key.

::: tip Runnable examples
[CardPay with HMAC and AES](/examples/cardpay) and [Pre-authorization and capture](/examples/cardpay-preauthorization)
run against payment-simulator in CI.
:::

## purchase() and completePurchase()

<<< @/snippets/cardpay-purchase.php#snippet

`name` and `ipc` are required. `completePurchase()` works like [TatraPay's](./tatrapay#completepurchase): pass `vs`
(and `amount`, `currency` for HMAC keys). It throws `InvalidRequestException` on a variable symbol mismatch or a wrong
signature; with an HMAC key the message contains the signed data to help debugging.

| `CompletePurchaseResponse` | Returns |
|---|---|
| `isSuccessful()` | `true` for `RES=OK` |
| `getRes()`, `getVs()` | result and verified variable symbol |
| `getRc()` | reason code of a declined payment (`RC`, when Tatra banka enabled it for you) |

## authorize() and completeAuthorize()

A pre-authorization (`TXN=PA`) blocks the amount on the card:

<figure class="ot-diagram">
  <img class="light-only" src="/diagrams/flow-preauthorization-light.svg" alt="Sequence: authorize() redirects to the bank, the bank blocks the amount and redirects back with RES, TID and HMAC, completeAuthorize() verifies and stores the TID, cancelAuthorize() captures, releases or refunds with a server-to-server request answered by a signed XML" width="760" height="512">
  <img class="dark-only" src="/diagrams/flow-preauthorization-dark.svg" alt="Sequence: authorize() redirects to the bank, the bank blocks the amount and redirects back with RES, TID and HMAC, completeAuthorize() verifies and stores the TID, cancelAuthorize() captures, releases or refunds with a server-to-server request answered by a signed XML" width="760" height="512">
</figure>

<<< @/snippets/cardpay-authorize.php#authorize

Optional parameters: `e2E`, `cid` and `ecid` (registered card), `tpay`, and the 3-D Secure data `tdsCardholder`,
`tdsEmail`, `tdsMobilePhone`, `tdsBillCity`, `tdsBillCountry`, `tdsBillAddress1`, `tdsBillAddress2`, `tdsBillZip`,
`tdsShipCity`, `tdsShipCountry`, `tdsShipAddress1`, `tdsShipAddress2`, `tdsShipZip`, `tdsAddrMatch`. Every value you
set is signed and sent.

On the return URL:

<<< @/snippets/cardpay-authorize.php#complete

| `CompleteAuthorizeResponse` | Returns |
|---|---|
| `isSuccessful()`, `getRes()`, `getVs()` | as above |
| `getTransactionReference()` | the bank's transaction id (`TID`), needed for `cancelAuthorize()` |
| `getAc()` | approval code |
| `getCc()` | masked card number |
| `getCid()` | id of the registered card |
| `getTres()` | 3-D Secure result |
| `getRc()` | reason code |

## cancelAuthorize(): capture, reversal, refund

A server-to-server `GET` to Tatra banka; the response body is XML signed in the `Authorization` header.

<<< @/snippets/cardpay-authorize.php#capture

| `txn` | Meaning |
|---|---|
| `CancelAuthorizeTxn::ClosePreauthorization` (`CPA`) | capture the pre-authorized amount (`amount` may be lower) |
| `CancelAuthorizeTxn::StornoPreauthorization` (`SPA`) | release the pre-authorization |
| `CancelAuthorizeTxn::Chargeback` (`CB`) | refund a captured payment |

Errors: an HTTP status other than 200 throws `Omnipay\Core\Exception\GatewayException`; a wrong response signature or
a body that is not XML throws `InvalidResponseException`. `CancelAuthorizeResponse::getRes()` is `OK` or `FAIL`,
`getErrorCode()` (also `getCode()`) is the bank's error code.
