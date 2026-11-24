---
title: Shop with a return URL
description: A minimal shop on the PHP built-in server with a TatraPay checkout and a return URL handler that verifies the callback, marks the order paid once and rejects edited and replayed callbacks.
---

# Shop with a return URL

The other examples call the library from one script. This one is a real web application: a shop on the PHP built-in
server with three URLs, and a second script that plays the customer's browser. It is the part of an integration that
attackers try: the return URL.

Source: [`examples/shop/`](https://github.com/tomaj/omnipay-tatra/tree/master/examples/shop) ·
run it with `examples/ci/example.sh shop`.

| URL | Does |
|---|---|
| `GET /checkout?amount=24.90` | creates an order with the next variable symbol and redirects to TatraPay |
| `GET /payment/return?...` | the bank's return: verify, then change the order |
| `GET /orders/<vs>` | shows the order's status |

The orders live in a JSON file
([`src/Orders.php`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/shop/src/Orders.php)), so the
example needs no database.

## The gateway

<<< @/../examples/shop/public/index.php#gateway

`Omnipay::create()` creates the gateway with the current HTTP request, so `completePurchase()` reads the bank's query
string by itself. The key comes from the environment; the fallback is the simulator's public test key.

## Checkout

<<< @/../examples/shop/public/index.php#checkout

## The return URL

<<< @/../examples/shop/public/index.php#return

The order of the checks matters:

1. **Find the order by the returned `VS`**, then verify the callback against that order's stored values, never
   against values from the query string.
2. **Verify before anything else.** A rejected callback answers `400` and changes nothing.
3. **Change the order once.** A reload of the return URL is a valid, correctly signed callback; the shop answers
   "already paid" instead of shipping twice.
4. **`TOUT` is pending, not paid.** The bank decides later and e-mails the result to `rem`.

## The customer

[`customer.php`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/shop/customer.php) opens the checkout,
follows the redirect to the simulator, picks an answer and opens the return URL, printing what the shop answers. It
also edits a failed payment's `RES=FAIL` to `RES=OK`, and sends a paid order's callback with the variable symbol of
another order.

## Output

<<< @/../examples/shop/expected-output.txt{text}

## What it tests

- `$response->redirect()` answers `302` to the simulator, which accepts the signature;
- a successful return marks the order paid, a reload does not pay it again;
- a failed and a timed-out payment set `failed` and `pending`;
- an edited result and a changed variable symbol are rejected with `400` and the order stays as it was.
