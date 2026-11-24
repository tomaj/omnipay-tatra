---
title: Podpisy a openssl
description: Ako omnipay-tatra podpíše požiadavku TatraPay HMAC, AES a DES kľúčom a tie isté podpisy vypočítané nezávisle príkazom openssl.
---

# Podpisy a openssl

Keď banka podpis odmietne, chcete vidieť podpisovaný reťazec a podpis si vypočítať sami. Tento príklad zostaví
požiadavku TatraPay s každým typom kľúča, poskladá podpisovaný reťazec z polí presmerovania tak, ako to robí banka,
a porovná podpis knižnice s podpisom vypočítaným príkazom `openssl`. Beží offline.

Zdrojový kód: [`examples/signatures/run.php`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/signatures/run.php) ·
spustíte ho príkazom `examples/ci/example.sh signatures`.

## Kľúče {#the-keys}

<<< @/../examples/signatures/run.php#keys

Dĺžka kľúča určuje algoritmus: 128 hexadecimálnych znakov HMAC-SHA256, 64 AES, 8 DES. Tieto kľúče sú pre príklad
vymyslené; pri akejkoľvek inej dĺžke sa vyhodí `UnsupportedKeyException`.

## Požiadavka {#the-request}

<<< @/../examples/signatures/run.php#request

`purchase()` nastaví `TIMESTAMP` na aktuálny čas v UTC; príklad ho zafixuje, aby bol HMAC pri každom spustení rovnaký.

## Podpisovaný reťazec {#the-signed-string}

<<< @/../examples/signatures/run.php#signed

Polia sa spájajú bez oddeľovačov a prázdne nepovinné pole nepridá nič. HMAC požiadavky podpisujú aj `REM` a
`TIMESTAMP`. Zoznamy polí každej požiadavky a návratu sú na stránke [Podpisy](/sk/guide/signatures#signed-fields).

## Ten istý podpis cez openssl {#the-same-signature-with-openssl}

<<< @/../examples/signatures/run.php#openssl

- **HMAC**: HMAC-SHA256 s kľúčom ako hex bajtmi, výsledok v malých hex znakoch.
- **AES**: prvých 16 bajtov SHA-1, zašifrovaných AES-128-ECB s **prvými 32 hex znakmi** kľúča, výsledok vo veľkých
  hex znakoch. Pozrite poznámku o [AES-128 a AES-256](/sk/guide/signatures#aes).
- **DES**: SHA-1 zašifrované DES-ECB, prvých 8 bajtov, veľké hex znaky. OpenSSL 3 potrebuje pre DES legacy provider,
  pre PHP (`OPENSSL_CONF` ukazujúce na
  [`openssl-legacy.cnf`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/signatures/openssl-legacy.cnf))
  aj pre príkazový riadok (`-provider legacy -provider default`).

Na macOS je `/usr/bin/openssl` LibreSSL, ktorému tieto voľby chýbajú: spustite príklad s
`OPENSSL=/opt/homebrew/opt/openssl@3/bin/openssl`.

## Výstup {#output}

<<< @/../examples/signatures/expected-output.txt{text}

## Čo príklad testuje {#what-it-tests}

- knižnica a nezávislá implementácia sa zhodnú na všetkých troch algoritmoch;
- poradie polí požiadavky TatraPay, s `REM` a `TIMESTAMP` aj bez nich.
