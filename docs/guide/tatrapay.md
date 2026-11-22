---
title: TatraPay
description: TatraPay payments from Tatra banka internet banking with omnipay-tatra - purchase, completePurchase and the response.
---

# TatraPay

TatraPay lets the customer pay from Tatra banka internet banking. The gateway has two requests: `purchase()` builds the
signed redirect, `completePurchase()` verifies the customer's return.

## purchase()

<<< @/snippets/tatrapay-purchase.php#snippet

| Parameter | Required | Notes |
|---|---|---|
| `amount`, `currency` | yes | |
| `vs` | yes | variable symbol of the order |
| `rurl` | yes | return URL |
| `cs`, `ss` | no | constant and specific symbol; both are signed and sent |
| `desc`, `rem`, `rsms`, `aredir`, `lang` | no | see [Configuration](./configuration#request-parameters) |

The response is a redirect (`isRedirect()` is `true`, `isSuccessful()` is `false`): call `redirect()` or send the
customer to `getRedirectUrl()` (a `GET` request).

## completePurchase()

<<< @/snippets/tatrapay-complete.php#snippet

Pass the same `vs`, `ss` and `cs` as to `purchase()`, and `amount` and `currency` for HMAC keys (they are part of the
signed string). The request:

1. throws `InvalidRequestException('The vs parameter is required')` without `vs`;
2. throws `InvalidRequestException('Variable symbol mismatch')` when the bank returned another `VS`;
3. throws `InvalidRequestException('incorect signature')` when the `HMAC`/`SIGN` does not match.

## CompletePurchaseResponse

| Method | Returns |
|---|---|
| `isSuccessful()` | `true` for `RES=OK` |
| `isPending()` | `true` for `RES=TOUT` (timeout: the bank confirms the result later) |
| `getRes()` | `OK`, `FAIL` or `TOUT` |
| `getVs()` | the verified variable symbol |
| `getData()` | `['RES' => ..., 'VS' => ...]` |
