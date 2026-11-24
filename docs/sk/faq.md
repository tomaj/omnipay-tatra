---
title: Časté otázky
description: Často kladené otázky o omnipay-tatra a službách TatraPay, CardPay a ComfortPay od Tatra banky.
---

# Časté otázky

## Aký kľúč mám? {#which-key-do-i-have}

Spočítajte znaky zdieľaného kľúča, ktorý vám dala Tatra banka: 128 hexadecimálnych znakov je HMAC, 64 je AES, 8 je
DES. Nové zmluvy dostávajú kľúče HMAC. Pozrite [Konfigurácia](/sk/guide/configuration#the-key-selects-the-algorithm).

## completePurchase() vyhodí „Variable symbol mismatch“ {#completepurchase-throws-variable-symbol-mismatch}

Odovzdali ste iný `vs`, ako vrátila banka. Odovzdajte variabilný symbol objednávky, ktorú dokončujete; vezmite ho zo
session alebo z objednávky vyhľadanej podľa query parametra `VS` a zachovajte úvodné nuly (`0123` a `123` sú rôzne).

## completePurchase() vyhodí „incorect signature“ {#completepurchase-throws-incorect-signature}

Callback bol zmenený, alebo ho overujete s inými hodnotami, ako ste podpísali: použite rovnaký kľúč, `vs`, `ss`, `cs`
a pri kľúčoch HMAC aj rovnaké `amount` a `currency` ako v `purchase()`.

## Podpisy DES zlyhajú s „Cannot sign with cipher des-ecb“ {#des-signatures-fail-with-cannot-sign-with-cipher-des-ecb}

OpenSSL 3 vypol DES. Zapnite legacy provider v `openssl.cnf`, alebo lepšie, požiadajte Tatra banku o kľúč HMAC.

## Banka zobrazí chybu pri mene zákazníka {#the-bank-shows-an-error-for-the-customer-name}

`name` smie obsahovať iba písmená bez diakritiky, číslice, medzeru a `. - _ @`; verzia 5.0 iné mená odmietne ešte pred
presmerovaním. Meno najprv prepíšte bez diakritiky.

## ComfortPay zlyhá s chybou SSL certifikátu {#comfortpay-fails-with-an-ssl-certificate-error}

Certifikát webovej služby sa overuje voči systémovému úložisku CA. Odovzdajte balík CA cez `caFile` alebo vlastný
`streamContext` ([TLS a proxy](/sk/guide/comfortpay#tls-and-proxy)); overovanie nevypínajte.

## Ako testovať bez banky? {#how-do-i-test-without-the-bank}

Použite [testovací režim](/sk/guide/test-mode), prípadne s vlastným simulátorom platobnej brány
[payment-simulator](https://github.com/tomaj/payment-simulator) ako `testHost`.

## Dá sa použiť bez Omnipay::create()? {#can-i-use-it-without-omnipay-create}

Áno: `new \Omnipay\TatraPay\Gateway($httpClient, $httpRequest)`. Callbacky čítajú query string z `$httpRequest`,
preto odovzdajte request aktuálnej stránky (`Request::createFromGlobals()`).

## Ako nájdem objednávku na návratovej URL? {#how-do-i-find-the-order-on-the-return-url}

Podľa query parametra `VS`: vyhľadajte podľa neho objednávku a potom odovzdajte `vs`, `amount` a `currency` tejto
objednávky do `completePurchase()`. Hodnoty z query stringu nikdy neodovzdávajte ako očakávané.
[Príklad e-shopu](/sk/examples/shop#the-return-url) robí presne toto.

## Zákazník znova načítal návratovú URL. Je to problém? {#the-customer-reloaded-the-return-url-is-that-a-problem}

Opätovné načítanie je správne podpísaný callback a overí sa znova. Objednávku meňte iba pri jej prvom úspešnom
návrate, ako v [príklade e-shopu](/sk/examples/shop#the-return-url).

## completePurchase() odmietne návrat z TatraPay so špecifickým symbolom {#completepurchase-rejects-a-tatrapay-return-with-a-specific-symbol}

Odovzdajte do `completePurchase()` rovnaký `ss` ako do `purchase()`. Banka podpisuje návrat aj nad ním (`VS SS RES`
pri kľúčoch AES a DES, `AMT CURR VS SS CS RES TID TIMESTAMP` pri kľúčoch HMAC) a payment-simulator tiež. Pozrite
[príklad TatraPay](/sk/examples/tatrapay#_5-with-a-specific-symbol).

## Posiela banka notifikáciu na môj server? {#is-there-a-notification-from-the-bank-to-my-server}

Pri týchto bránach nie: výsledok prichádza so zákazníkom na návratovú URL. Nastavte `rem`, ak chcete výsledok dostať
aj e-mailom, a `aredir` na `true`, aby banka poslala zákazníka späť bez čakania na kliknutie. Objednávka, ktorej
zákazník sa nikdy nevrátil, ostane vo vašom e-shope nezaplatená; overte ju podľa e-mailu alebo v portáli obchodníka
v banke.
