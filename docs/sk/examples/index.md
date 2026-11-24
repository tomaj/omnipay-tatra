---
title: Príklady
description: Šesť kompletných spustiteľných receptov pre omnipay-tatra - TatraPay, CardPay s HMAC a AES kľúčom, predautorizácia s dokončením a vrátením platby, predplatné cez ComfortPay, návratová URL e-shopu a podpisy overené cez openssl.
---

# Príklady

Každý príklad je spustiteľný projekt v priečinku
[`examples/`](https://github.com/tomaj/omnipay-tatra/tree/master/examples) repozitára. Kód na týchto stránkach sa
vkladá priamo z týchto súborov a [workflow Examples](https://github.com/tomaj/omnipay-tatra/blob/master/.github/workflows/examples.yml)
spustí každý z nich pri každom pull requeste proti kódu daného pull requestu a porovná výstup s jeho
`expected-output.txt`. Nič z toho nekomunikuje s Tatra bankou ani nepresúva peniaze: platby idú na lokálny
[simulátor platobnej brány payment-simulator](/sk/guide/test-mode#payment-simulator) a webová služba ComfortPay
odpovedá v testovacom režime.

| Príklad | Ukazuje | Beží proti |
|---|---|---|
| [Platba cez TatraPay od začiatku do konca](./tatrapay) | `purchase()`, každú odpoveď banky overenú cez `completePurchase()`, odmietnutý podvrhnutý a cudzí návrat z banky | simulátor |
| [CardPay s HMAC a AES kľúčom](./cardpay) | ten istý kód s oboma typmi kľúčov, kontrolu mena zákazníka | simulátor |
| [Predautorizácia a dokončenie](./cardpay-preauthorization) | `authorize()` s údajmi pre 3-D Secure, `completeAuthorize()`, dokončenie, zrušenie predautorizácie a vrátenie platby cez `cancelAuthorize()` | simulátor |
| [Predplatné cez ComfortPay](./comfortpay-recurring) | registráciu karty, opakovanú platbu `charge()`, `checkTransaction()`, `checkCard()`, expiráciu karty, `unRegisterCard()` | simulátor + testovací režim |
| [E-shop s návratovou URL](./shop) | e-shop na vstavanom PHP serveri: pokladňu, spracovanie návratovej URL, opakované načítanie, upravené a znovu poslané návraty z banky | simulátor |
| [Podpisy a openssl](./signatures) | podpisovaný reťazec pre každý typ kľúča, overený príkazom `openssl` | offline |

## Spustenie {#run-them}

```bash
git clone https://github.com/tomaj/omnipay-tatra.git
cd omnipay-tatra
examples/ci/example.sh tatrapay      # or cardpay, cardpay-preauthorization, comfortpay-recurring, shop, signatures
```

[`examples/ci/example.sh`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/ci/example.sh) nainštaluje
príklad cez Composer (jeho `composer.json` berie `tomaj/omnipay-tatra` z koreňa repozitára cez path repository),
spustí payment-simulator skriptom
[`examples/ci/start-simulator.sh`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/ci/start-simulator.sh),
ak ešte nebeží, a spustí `e2e.sh` príkladu, ktorý vypíše `ok: <example>`, keď výstup sedí.
Potrebujete PHP 8.4+ s rozšíreniami `openssl`, `soap` a `iconv`, Composer, git a curl. Ak simulátor beží inde,
napríklad v Dockeri, nastavte `SIMULATOR_URL`.

Ručne spustíte príklad príkazom `cd examples/tatrapay && composer install && php run.php`.

Príklady zdieľajú jeden pomocný súbor, [`examples/_support/simulator.php`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/_support/simulator.php),
ktorý v simulátore hrá rolu zákazníka: otvorí platobnú stránku, prečíta verdikt a tri podpísané odkazy späť do
e-shopu.

<<< @/../examples/_support/simulator.php#pay
