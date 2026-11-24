---
title: Pre-authorization and capture
description: CardPay pre-authorization against payment-simulator - authorize() with 3-D Secure data, completeAuthorize(), then capture, release and refund with cancelAuthorize().
---

# Pre-authorization and capture

Block the amount on the customer's card when the order is placed and take the money when the goods ship. The example
pre-authorizes 120.00 €, captures 110.00 €, and shows the release of a pre-authorization and the refund of a captured
payment. `authorize()` and `cancelAuthorize()` need an HMAC key.

Source: [`examples/cardpay-preauthorization/run.php`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/cardpay-preauthorization/run.php) ·
run it with `examples/ci/example.sh cardpay-preauthorization`.

<figure class="ot-diagram">
  <img class="light-only" src="/diagrams/flow-preauthorization-light.svg" alt="Sequence: authorize() redirects the customer to the bank, which blocks the amount and redirects back with RES, TID and HMAC; completeAuthorize() verifies and stores the TID; cancelAuthorize() captures, releases or refunds with a server-to-server request answered by a signed XML" width="760" height="512">
  <img class="dark-only" src="/diagrams/flow-preauthorization-dark.svg" alt="Sequence: authorize() redirects the customer to the bank, which blocks the amount and redirects back with RES, TID and HMAC; completeAuthorize() verifies and stores the TID; cancelAuthorize() captures, releases or refunds with a server-to-server request answered by a signed XML" width="760" height="512">
</figure>

## 1. Pre-authorize

<<< @/../examples/cardpay-preauthorization/run.php#authorize

`authorize()` sends `TXN=PA`. Every 3-D Secure field you set (`tdsEmail`, `tdsBillCity`, ... see
[CardPay](/guide/cardpay#authorize-and-completeauthorize)) is signed and sent; the bank uses them to skip the
cardholder challenge where it can. `tpay` `Y` also registers the card, if your contract includes ComfortPay.

## 2. Verify the return and keep the transaction id

<<< @/../examples/cardpay-preauthorization/run.php#complete

`getTransactionReference()` is the bank's `TID`. Store it with the order: every later operation refers to it.

## 3. Capture

<<< @/../examples/cardpay-preauthorization/run.php#capture

`cancelAuthorize()` is a server-to-server `GET` to the bank, signed with HMAC. The bank answers with XML, signed in the
`Authorization` header, which the library verifies before it reads `<res>`. The captured amount may be lower than the
pre-authorized one.

## 4. Release or refund

<<< @/../examples/cardpay-preauthorization/run.php#release

| `txn` | Use it when |
|---|---|
| `CancelAuthorizeTxn::ClosePreauthorization` (`CPA`) | the goods ship: take the money |
| `CancelAuthorizeTxn::StornoPreauthorization` (`SPA`) | the order is cancelled before capture: release the block |
| `CancelAuthorizeTxn::Chargeback` (`CB`) | the customer returns captured goods: refund |

## 5. A refused request

<<< @/../examples/cardpay-preauthorization/run.php#wrong-key

The simulator refuses a request signed with another key with `<res>FAIL</res>` and error code `10`, like the bank.
See [Error handling](/guide/errors#capture-release-and-refund) for the other outcomes.

## Output

<<< @/../examples/cardpay-preauthorization/expected-output.txt{text}

## What it tests

- the simulator accepts the `authorize()` signature over all 3-D Secure fields;
- `completeAuthorize()` verifies the return and exposes `TID`, `AC`, `CC` and `CID`;
- `cancelAuthorize()` signs its request and verifies the signed XML answer for `CPA`, `SPA` and `CB`;
- a refused request is an unsuccessful response with the bank's error code, not an exception.
