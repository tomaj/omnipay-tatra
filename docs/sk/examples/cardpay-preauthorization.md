---
title: Predautorizácia a dokončenie
description: Predautorizácia CardPay proti simulátoru payment-simulator - authorize() s údajmi pre 3-D Secure, completeAuthorize(), potom dokončenie, zrušenie predautorizácie a vrátenie platby cez cancelAuthorize().
---

# Predautorizácia a dokončenie

Pri vytvorení objednávky zablokujte sumu na karte zákazníka a peniaze si stiahnite, až keď tovar odošlete. Príklad
predautorizuje 120.00 €, dokončí 110.00 € a ukáže zrušenie predautorizácie aj vrátenie už stiahnutej platby.
`authorize()` a `cancelAuthorize()` potrebujú HMAC kľúč.

Zdrojový kód: [`examples/cardpay-preauthorization/run.php`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/cardpay-preauthorization/run.php) ·
spustíte ho príkazom `examples/ci/example.sh cardpay-preauthorization`.

<figure class="ot-diagram">
  <img class="light-only" src="/diagrams/flow-preauthorization-sk-light.svg" alt="Sekvencia: authorize() presmeruje zákazníka do banky, ktorá zablokuje sumu a presmeruje ho späť s RES, TID a HMAC; completeAuthorize() návrat overí a uloží TID; cancelAuthorize() predautorizáciu dokončí, zruší alebo vráti platbu server-to-server požiadavkou, na ktorú banka odpovie podpísaným XML" width="760" height="512">
  <img class="dark-only" src="/diagrams/flow-preauthorization-sk-dark.svg" alt="Sekvencia: authorize() presmeruje zákazníka do banky, ktorá zablokuje sumu a presmeruje ho späť s RES, TID a HMAC; completeAuthorize() návrat overí a uloží TID; cancelAuthorize() predautorizáciu dokončí, zruší alebo vráti platbu server-to-server požiadavkou, na ktorú banka odpovie podpísaným XML" width="760" height="512">
</figure>

## 1. Predautorizácia {#_1-pre-authorize}

<<< @/../examples/cardpay-preauthorization/run.php#authorize

`authorize()` posiela `TXN=PA`. Každé pole pre 3-D Secure, ktoré nastavíte (`tdsEmail`, `tdsBillCity`, ... pozrite
[CardPay](/sk/guide/cardpay#authorize-and-completeauthorize)), sa podpíše a odošle; banka ich využije na to, aby
podľa možnosti preskočila overenie držiteľa karty. `tpay` `Y` zároveň zaregistruje kartu, ak vaša zmluva zahŕňa
ComfortPay.

## 2. Overenie návratu a uloženie ID transakcie {#_2-verify-the-return-and-keep-the-transaction-id}

<<< @/../examples/cardpay-preauthorization/run.php#complete

`getTransactionReference()` je `TID` od banky. Uložte ho k objednávke: odkazuje naň každá ďalšia operácia.

## 3. Dokončenie {#_3-capture}

<<< @/../examples/cardpay-preauthorization/run.php#capture

`cancelAuthorize()` je server-to-server `GET` požiadavka do banky podpísaná cez HMAC. Banka odpovie XML podpísaným
v hlavičke `Authorization`, ktoré knižnica overí skôr, než prečíta `<res>`. Dokončená suma môže byť nižšia ako
predautorizovaná.

## 4. Zrušenie alebo vrátenie platby {#_4-release-or-refund}

<<< @/../examples/cardpay-preauthorization/run.php#release

| `txn` | Použite, keď |
|---|---|
| `CancelAuthorizeTxn::ClosePreauthorization` (`CPA`) | odosielate tovar: stiahnite peniaze |
| `CancelAuthorizeTxn::StornoPreauthorization` (`SPA`) | objednávka sa zruší pred dokončením: uvoľnite blokáciu |
| `CancelAuthorizeTxn::Chargeback` (`CB`) | zákazník vráti tovar po dokončení: vráťte platbu |

## 5. Odmietnutá požiadavka {#_5-a-refused-request}

<<< @/../examples/cardpay-preauthorization/run.php#wrong-key

Simulátor odmietne požiadavku podpísanú iným kľúčom odpoveďou `<res>FAIL</res>` s chybovým kódom `10`, rovnako ako
banka. Ostatné možné výsledky nájdete v časti [Spracovanie chýb](/sk/guide/errors#capture-release-and-refund).

## Výstup {#output}

<<< @/../examples/cardpay-preauthorization/expected-output.txt{text}

## Čo príklad testuje {#what-it-tests}

- simulátor prijme podpis `authorize()` nad všetkými poľami pre 3-D Secure;
- `completeAuthorize()` overí návrat a sprístupní `TID`, `AC`, `CC` a `CID`;
- `cancelAuthorize()` podpíše svoju požiadavku a overí podpísanú XML odpoveď pre `CPA`, `SPA` aj `CB`;
- odmietnutá požiadavka je neúspešná odpoveď s chybovým kódom banky, nie výnimka.
