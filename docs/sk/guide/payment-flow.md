---
title: Ako prebieha platba
description: Priebeh platby s presmerovaním pri TatraPay, CardPay a ComfortPay krok za krokom – čo podpisuje purchase(), čo banka pošle späť a čo overí completePurchase().
---

# Ako prebieha platba

TatraPay, CardPay aj ComfortPay začínajú rovnako: zákazník odíde z vášho e-shopu na platobnú stránku Tatra banky
a vráti sa na vašu návratovú URL. Ani jedna z týchto ciest nevedie cez priame spojenie vášho servera s bankou, preto
sú obe podpísané kľúčom, ktorý máte s bankou spoločný.

<figure class="ot-diagram">
  <img class="light-only" src="/diagrams/flow-tatrapay-sk-light.svg" alt="Sekvencia: zákazník zaplatí v e-shope; purchase() odpovie presmerovaním 302 do Tatra banky s podpísanými poľami; zákazník zaplatí v banke; banka presmeruje zákazníka na návratovú URL s VS, RES, TIMESTAMP a HMAC; completePurchase() overí podpis a variabilný symbol" width="760" height="512">
  <img class="dark-only" src="/diagrams/flow-tatrapay-sk-dark.svg" alt="Sekvencia: zákazník zaplatí v e-shope; purchase() odpovie presmerovaním 302 do Tatra banky s podpísanými poľami; zákazník zaplatí v banke; banka presmeruje zákazníka na návratovú URL s VS, RES, TIMESTAMP a HMAC; completePurchase() overí podpis a variabilný symbol" width="760" height="512">
</figure>

## 1. Objednávka dostane variabilný symbol {#_1-the-order-gets-a-variable-symbol}

Variabilný symbol (`vs`, najviac 10 číslic) je spôsob, akým vy aj banka označujete objednávku. Vytvorte ho, keď
zákazník odošle objednávku, a uložte ho k nej: budete ho znova potrebovať na overenie návratu. Pre každý pokus
o platbu, ktorý chcete rozlíšiť, použite nový.

## 2. purchase() zostaví podpísané presmerovanie {#_2-purchase-builds-the-signed-redirect}

`purchase()` banku nevolá. Poskladá polia (`MID`, `AMT`, `CURR`, `VS`, `RURL` a voliteľné polia), podpíše ich vaším
kľúčom a vráti odpoveď s presmerovaním:

| Metóda | Vracia |
|---|---|
| `isRedirect()` | vždy `true` |
| `isSuccessful()` | vždy `false`: zatiaľ nie je nič zaplatené |
| `getRedirectUrl()` | adresu banky s poľami v query stringu |
| `getRedirectMethod()` | `GET` |
| `getRedirectData()` | polia ako pole, s `HMAC` alebo `SIGN` |
| `redirect()` | pošle `302` na `getRedirectUrl()` a skončí |

Adresa závisí od brány, [typu kľúča](./configuration#the-key-selects-the-algorithm) a
[testovacieho režimu](./test-mode).

## 3. Zákazník zaplatí v banke {#_3-the-customer-pays-at-the-bank}

Banka overí podpis, zobrazí svoju platobnú stránku a zákazník zaplatí, platbu zruší, alebo banka nestihne odpovedať
(timeout). Váš e-shop sa na tom nepodieľa. S `rem` banka pošle výsledok aj e-mailom na túto adresu, s `rsms` pošle SMS.

## 4. Banka pošle zákazníka späť {#_4-the-bank-sends-the-customer-back}

Banka presmeruje zákazníka na `rurl` s výsledkom v query stringu, podpísaným tým istým kľúčom:

```text
https://shop.example/payment/return?VS=2026001&RES=OK&AMT=24.90&CURR=978&TID=...&TIMESTAMP=24112026101500&HMAC=...
```

`RES` je `OK` (zaplatené), `FAIL` (nezaplatené) alebo pri TatraPay `TOUT` (timeout: banka výsledok potvrdí neskôr).
Túto URL nesie prehliadač zákazníka, takže ju zákazník môže čítať, upraviť aj znova načítať.

## 5. completePurchase() overí návrat {#_5-completepurchase-verifies-the-return}

Na návratovej URL zavolajte `completePurchase()` s objednávkou, ktorú očakávate. Query string prečíta z požiadavky,
s ktorou bola brána vytvorená (`Omnipay::create()` berie aktuálnu požiadavku z globálnych premenných PHP), a:

1. porovná vrátený `VS` s `vs`, ktorý odovzdáte, a pri inej objednávke vyhodí
   `InvalidRequestException('Variable symbol mismatch')`;
2. vypočíta podpis z **vašich** očakávaných hodnôt (`vs` a tam, kde sú podpísané, aj `amount`, `currency`, `ss`, `cs`)
   a vrátených polí a vyhodí `InvalidRequestException`, keď sa líši od `HMAC`/`SIGN`; porovnáva sa cez `hash_equals()`;
3. vráti overený výsledok: `isSuccessful()` je `true` iba pre `RES=OK`.

<<< @/../examples/tatrapay/run.php#complete

Objednávku nájdite podľa parametra `VS` v query stringu a potom odovzdajte hodnoty uloženej objednávky, ako to robí
[príklad e-shopu](/sk/examples/shop). Až potom označte objednávku ako zaplatenú, a to iba raz: opätovné načítanie
návratovej URL ju overí znova.

## 6. Čo podpis pokrýva {#_6-what-the-signature-covers}

| Kľúč | Podpísané polia návratu | Dôsledok |
|---|---|---|
| HMAC (128 znakov) | suma, mena, VS, výsledok, ID transakcie, časová pečiatka (a ďalšie podľa brány) | spätné volanie s inou sumou neprejde overením podpisu |
| AES (64) / DES (8) | VS a výsledok (CardPay aj autorizačný kód) | **sumu porovnajte sami**: nie je podpísaná |

Presné zoznamy polí sú na stránke [Podpisy](./signatures#signed-fields).

## Server-to-server požiadavky {#server-to-server-requests}

Tri operácie neprechádzajú prehliadačom zákazníka:

- CardPay `cancelAuthorize()`: dokončenie, zrušenie predautorizácie alebo vrátenie platby, podpísaný `GET` do banky,
  ktorej XML odpoveď je podpísaná v hlavičke `Authorization`
  ([priebeh predautorizácie](./cardpay#authorize-and-completeauthorize));
- webová služba ComfortPay (`charge()`, `checkCard()`, ...): SOAP cez TLS s vaším klientskym certifikátom
  ([ComfortPay](./comfortpay#the-web-service)).
