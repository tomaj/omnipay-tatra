---
title: TatraPay
description: TatraPay payments from Tatra banka internet banking with omnipay-tatra - purchase, completePurchase and the response.
---

# TatraPay

TatraPay lets the customer pay from Tatra banka internet banking. The gateway has two requests: `purchase()` builds the
signed redirect, `completePurchase()` verifies the customer's return ([how a payment works](./payment-flow)).

::: tip Runnable example
[TatraPay round trip](/examples/tatrapay) runs this flow against payment-simulator in CI, including a forged and a
foreign callback.
:::

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

`CompletePurchaseResponse::TIMEOUT` is the `'TOUT'` constant, `AbstractCallbackResponse::SUCCESS` is `'OK'`.

## Timeouts

`RES=TOUT` means the bank did not get a final answer in time. Do not mark the order paid or failed: keep it pending.
Tatra banka sends the final result to the e-mail address in `rem` (and by SMS to `rsms`), so set `rem` to an address
your shop reads.
