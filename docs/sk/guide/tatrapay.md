---
title: TatraPay
description: Platby TatraPay z internet bankingu Tatra banky s omnipay-tatra – purchase, completePurchase a odpoveď.
---

# TatraPay

TatraPay umožňuje zákazníkovi zaplatiť z internet bankingu Tatra banky. Brána má dve požiadavky: `purchase()` zostaví
podpísané presmerovanie, `completePurchase()` overí návrat zákazníka ([ako prebieha platba](./payment-flow)).

::: tip Spustiteľný príklad
[TatraPay od začiatku do konca](/sk/examples/tatrapay) spúšťa tento priebeh v CI proti simulátoru platobnej brány
payment-simulator, vrátane podvrhnutého a cudzieho spätného volania.
:::

## purchase() {#purchase}

<<< @/snippets/tatrapay-purchase.php#snippet

| Parameter | Povinný | Poznámky |
|---|---|---|
| `amount`, `currency` | áno | |
| `vs` | áno | variabilný symbol objednávky |
| `rurl` | áno | návratová URL |
| `cs`, `ss` | nie | konštantný a špecifický symbol; oba sa podpisujú a posielajú |
| `desc`, `rem`, `rsms`, `aredir`, `lang` | nie | pozrite [Konfigurácia](./configuration#request-parameters) |

Odpoveď je presmerovanie (`isRedirect()` je `true`, `isSuccessful()` je `false`): zavolajte `redirect()` alebo
pošlite zákazníka na `getRedirectUrl()` (požiadavka `GET`).

## completePurchase() {#completepurchase}

<<< @/snippets/tatrapay-complete.php#snippet

Odovzdajte rovnaké `vs`, `ss` a `cs` ako do `purchase()` a pri HMAC kľúčoch aj `amount` a `currency` (sú súčasťou
podpisovaného reťazca). Požiadavka:

1. vyhodí `InvalidRequestException('The vs parameter is required')`, keď chýba `vs`;
2. vyhodí `InvalidRequestException('Variable symbol mismatch')`, keď banka vrátila iný `VS`;
3. vyhodí `InvalidRequestException('incorect signature')`, keď `HMAC`/`SIGN` nesedí.

## CompletePurchaseResponse {#completepurchaseresponse}

| Metóda | Vracia |
|---|---|
| `isSuccessful()` | `true` pre `RES=OK` |
| `isPending()` | `true` pre `RES=TOUT` (timeout: banka výsledok potvrdí neskôr) |
| `getRes()` | `OK`, `FAIL` alebo `TOUT` |
| `getVs()` | overený variabilný symbol |
| `getData()` | `['RES' => ..., 'VS' => ...]` |

`CompletePurchaseResponse::TIMEOUT` je konštanta `'TOUT'`, `AbstractCallbackResponse::SUCCESS` je `'OK'`.

## Timeouty {#timeouts}

`RES=TOUT` znamená, že banka nedostala konečnú odpoveď včas. Objednávku neoznačujte ako zaplatenú ani neúspešnú:
nechajte ju v stave „čaká sa“. Tatra banka pošle konečný výsledok na e-mailovú adresu v `rem` (a cez SMS na `rsms`),
preto do `rem` nastavte adresu, ktorú váš e-shop číta.
