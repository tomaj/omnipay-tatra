---
title: CardPay s HMAC a AES kľúčom
description: Platby kartou cez CardPay proti simulátoru payment-simulator s HMAC aj AES kľúčom - ten istý kód purchase() a completePurchase() a kontrola mena zákazníka.
---

# CardPay s HMAC a AES kľúčom

Pri CardPay zadáva zákazník kartu na stránke Tatra banky. Príklad spustí ten istý kód dvakrát: so 128-znakovým HMAC
kľúčom (ktorý Tatra banka vydáva dnes) a so 64-znakovým AES kľúčom (staršie zmluvy). Dĺžka `sharedSecret` určí
algoritmus, adresu brány aj podpisované polia; váš kód sa nemení.

Zdrojový kód: [`examples/cardpay/run.php`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/cardpay/run.php) ·
spustíte ho príkazom `examples/ci/example.sh cardpay`.

## Platba {#the-payment}

<<< @/../examples/cardpay/run.php#purchase

CardPay potrebuje dve polia, ktoré TatraPay nepotrebuje: `name`, meno držiteľa karty, a `ipc`, IP adresu zákazníka
(ak ju vynecháte, vezme sa z `REMOTE_ADDR`).

| | HMAC kľúč | AES kľúč |
|---|---|---|
| adresa brány | `.../cardpay` (`/payment/cardpay-hmac` v testovacom režime) | `.../e-commerce.jsp` (`/payment/cardpay-aes256`) |
| podpis požiadavky v poli | `HMAC`, s `TIMESTAMP` | `SIGN` |
| podpis návratu nad poliami | `AMT CURR VS RES AC TRES CC RC TID TIMESTAMP` | `VS RES AC` |
| suma overená pri návrate | áno | **nie**: porovnajte ju sami |

## Návrat {#the-return}

<<< @/../examples/cardpay/run.php#complete

`amount` a `currency` sú súčasťou podpisovaného reťazca len pri HMAC kľúčoch; keď ich odovzdáte vždy, nič tým
nepokazíte.

## Mená zákazníkov {#customer-names}

Banka v poli `NAME` prijme len písmená bez diakritiky, číslice, medzeru a `. - _ @`. Knižnica meno skontroluje ešte pred
presmerovaním, takže zákazník nikdy neuvidí chybovú stránku banky:

<<< @/../examples/cardpay/run.php#name

## Výstup {#output}

<<< @/../examples/cardpay/expected-output.txt{text}

## Čo príklad testuje {#what-it-tests}

- oba typy kľúčov vytvoria požiadavky, ktoré simulátor prijme, na správnej adrese a so správnym poľom podpisu;
- oba druhy návratu overí to isté volanie `completePurchase()`;
- meno s diakritikou sa odmietne výnimkou `InvalidRequestException` a prepis bez diakritiky je platný.
