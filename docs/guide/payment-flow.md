---
title: How a payment works
description: The redirect payment flow of TatraPay, CardPay and ComfortPay step by step - what purchase() signs, what the bank sends back and what completePurchase() verifies.
---

# How a payment works

TatraPay, CardPay and ComfortPay all start the same way: the customer leaves your shop for Tatra banka's payment page
and comes back to your return URL. Neither leg goes through your server-to-server connection, so both are signed with
the key you share with the bank.

<figure class="ot-diagram">
  <img class="light-only" src="/diagrams/flow-tatrapay-light.svg" alt="Sequence: the customer pays in the shop; purchase() answers with a 302 redirect to Tatra banka carrying the signed fields; the customer pays at the bank; the bank redirects the customer to the return URL with VS, RES, TIMESTAMP and HMAC; completePurchase() verifies the signature and the variable symbol" width="760" height="512">
  <img class="dark-only" src="/diagrams/flow-tatrapay-dark.svg" alt="Sequence: the customer pays in the shop; purchase() answers with a 302 redirect to Tatra banka carrying the signed fields; the customer pays at the bank; the bank redirects the customer to the return URL with VS, RES, TIMESTAMP and HMAC; completePurchase() verifies the signature and the variable symbol" width="760" height="512">
</figure>

## 1. The order gets a variable symbol

The variable symbol (`vs`, up to 10 digits) is how you and the bank refer to the order. Create it when the customer
places the order and store it with the order: you need it again to verify the return. Use a new one for every payment
attempt you want to tell apart.

## 2. purchase() builds the signed redirect

`purchase()` does not call the bank. It puts the fields together (`MID`, `AMT`, `CURR`, `VS`, `RURL` and the optional
ones), signs them with your key and returns a redirect response:

| Method | Returns |
|---|---|
| `isRedirect()` | always `true` |
| `isSuccessful()` | always `false`: nothing is paid yet |
| `getRedirectUrl()` | the bank's endpoint with the fields in the query string |
| `getRedirectMethod()` | `GET` |
| `getRedirectData()` | the fields as an array, with `HMAC` or `SIGN` |
| `redirect()` | sends a `302` to `getRedirectUrl()` and exits |

The endpoint depends on the gateway, the [key type](./configuration#the-key-selects-the-algorithm) and
[test mode](./test-mode).

## 3. The customer pays at the bank

The bank checks the signature, shows its payment page and the customer pays, cancels, or the bank times out. Your shop
is not involved. With `rem` the bank also e-mails the result to that address, with `rsms` it sends an SMS.

## 4. The bank sends the customer back

The bank redirects the customer to `rurl` with the result in the query string, signed with the same key:

```text
https://shop.example/payment/return?VS=2026001&RES=OK&AMT=24.90&CURR=978&TID=...&TIMESTAMP=24112026101500&HMAC=...
```

`RES` is `OK` (paid), `FAIL` (not paid) or, for TatraPay, `TOUT` (timeout: the bank confirms the result later). The
customer's browser carries this URL, so the customer can read, edit and reload it.

## 5. completePurchase() verifies the return

On the return URL, call `completePurchase()` with the order you expect. It reads the query string from the request the
gateway was created with (`Omnipay::create()` takes the current request from the PHP globals) and:

1. compares the returned `VS` with the `vs` you pass, and throws `InvalidRequestException('Variable symbol mismatch')`
   for another order;
2. computes the signature from **your** expected values (`vs`, and `amount`, `currency`, `ss`, `cs` where they are
   signed) and the returned fields, and throws `InvalidRequestException` when it differs from `HMAC`/`SIGN`, compared
   with `hash_equals()`;
3. returns the verified result: `isSuccessful()` is `true` only for `RES=OK`.

<<< @/../examples/tatrapay/run.php#complete

Find the order by the `VS` query parameter, then pass the stored order's values, as the
[shop example](/examples/shop) does. Only then mark the order paid, and only once: a reload of the return URL verifies
again.

## 6. What the signature covers

| Key | Signed return fields | Consequence |
|---|---|---|
| HMAC (128 characters) | amount, currency, VS, result, transaction id, timestamp (and more, per gateway) | a callback for another amount fails the signature |
| AES (64) / DES (8) | VS and result (CardPay also the approval code) | **compare the amount yourself**: it is not signed |

The exact field lists are on the [Signatures](./signatures#signed-fields) page.

## Server-to-server requests

Three operations do not go through the customer's browser:

- CardPay `cancelAuthorize()`: capture, release or refund, a signed `GET` to the bank whose XML answer is signed in the
  `Authorization` header ([pre-authorization flow](./cardpay#authorize-and-completeauthorize));
- the ComfortPay web service (`charge()`, `checkCard()`, ...): SOAP over TLS with your client certificate
  ([ComfortPay](./comfortpay#the-web-service)).
