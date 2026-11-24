---
title: CardPay
description: Platby kartou cez CardPay s omnipay-tatra – purchase, predautorizácia s údajmi pre 3-D Secure, dokončenie, zrušenie predautorizácie a vrátenie platby.
---

# CardPay

CardPay prijíma platby kartou na stránke Tatra banky. Okrem bežnej platby podporuje predautorizáciu, ktorú neskôr
dokončíte, zrušíte alebo vrátite server-to-server požiadavkou. `authorize()` a `cancelAuthorize()` potrebujú HMAC
kľúč.

::: tip Spustiteľné príklady
[CardPay s HMAC a AES](/sk/examples/cardpay) a [Predautorizácia a dokončenie](/sk/examples/cardpay-preauthorization)
bežia v CI proti simulátoru platobnej brány payment-simulator.
:::

## purchase() a completePurchase() {#purchase-and-completepurchase}

<<< @/snippets/cardpay-purchase.php#snippet

`name` a `ipc` sú povinné. `completePurchase()` funguje rovnako ako [pri TatraPay](./tatrapay#completepurchase):
odovzdajte `vs` (a pri HMAC kľúčoch aj `amount`, `currency`). Pri nezhode variabilného symbolu alebo nesprávnom
podpise vyhodí `InvalidRequestException`; pri HMAC kľúči správa obsahuje podpísané údaje, aby sa chyba ľahšie hľadala.

| `CompletePurchaseResponse` | Vracia |
|---|---|
| `isSuccessful()` | `true` pre `RES=OK` |
| `getRes()`, `getVs()` | výsledok a overený variabilný symbol |
| `getRc()` | kód dôvodu zamietnutej platby (`RC`, ak vám ho Tatra banka zapla) |

## authorize() a completeAuthorize() {#authorize-and-completeauthorize}

Predautorizácia (`TXN=PA`) zablokuje sumu na karte:

<figure class="ot-diagram">
  <img class="light-only" src="/diagrams/flow-preauthorization-sk-light.svg" alt="Sekvencia: authorize() presmeruje do banky, banka zablokuje sumu a presmeruje späť s RES, TID a HMAC, completeAuthorize() overí a uloží TID, cancelAuthorize() dokončí, zruší predautorizáciu alebo vráti platbu server-to-server požiadavkou, na ktorú banka odpovie podpísaným XML" width="760" height="512">
  <img class="dark-only" src="/diagrams/flow-preauthorization-sk-dark.svg" alt="Sekvencia: authorize() presmeruje do banky, banka zablokuje sumu a presmeruje späť s RES, TID a HMAC, completeAuthorize() overí a uloží TID, cancelAuthorize() dokončí, zruší predautorizáciu alebo vráti platbu server-to-server požiadavkou, na ktorú banka odpovie podpísaným XML" width="760" height="512">
</figure>

<<< @/snippets/cardpay-authorize.php#authorize

Voliteľné parametre: `e2E`, `cid` a `ecid` (registrovaná karta), `tpay` a údaje pre 3-D Secure `tdsCardholder`,
`tdsEmail`, `tdsMobilePhone`, `tdsBillCity`, `tdsBillCountry`, `tdsBillAddress1`, `tdsBillAddress2`, `tdsBillZip`,
`tdsShipCity`, `tdsShipCountry`, `tdsShipAddress1`, `tdsShipAddress2`, `tdsShipZip`, `tdsAddrMatch`. Každá hodnota,
ktorú nastavíte, sa podpíše a pošle.

Na návratovej URL:

<<< @/snippets/cardpay-authorize.php#complete

| `CompleteAuthorizeResponse` | Vracia |
|---|---|
| `isSuccessful()`, `getRes()`, `getVs()` | ako vyššie |
| `getTransactionReference()` | ID transakcie v banke (`TID`), potrebné pre `cancelAuthorize()` |
| `getAc()` | autorizačný kód |
| `getCc()` | maskované číslo karty |
| `getCid()` | ID registrovanej karty |
| `getTres()` | výsledok 3-D Secure |
| `getRc()` | kód dôvodu |

## cancelAuthorize(): dokončenie, zrušenie, vrátenie platby {#cancelauthorize-capture-reversal-refund}

Server-to-server `GET` do Tatra banky; telo odpovede je XML podpísané v hlavičke `Authorization`.

<<< @/snippets/cardpay-authorize.php#capture

| `txn` | Význam |
|---|---|
| `CancelAuthorizeTxn::ClosePreauthorization` (`CPA`) | dokončenie predautorizácie, strhne sa predautorizovaná suma (`amount` môže byť nižšia) |
| `CancelAuthorizeTxn::StornoPreauthorization` (`SPA`) | zrušenie predautorizácie, uvoľní zablokovanú sumu |
| `CancelAuthorizeTxn::Chargeback` (`CB`) | vrátenie strhnutej platby (refundácia) |

Chyby: iný stav HTTP než 200 vyhodí `Omnipay\Core\Exception\GatewayException`; nesprávny podpis odpovede alebo telo,
ktoré nie je XML, vyhodí `InvalidResponseException`. `CancelAuthorizeResponse::getRes()` je `OK` alebo `FAIL`,
`getErrorCode()` (aj `getCode()`) je chybový kód banky.
