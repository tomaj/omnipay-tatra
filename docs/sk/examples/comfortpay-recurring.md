---
title: Predplatné cez ComfortPay
description: Mesačné predplatné cez ComfortPay - prvá platba zaregistruje kartu v simulátore payment-simulator, ďalšie mesiace sa karta zaťaží cez webovú službu a keď zákazník predplatné zruší, karta sa odregistruje.
---

# Predplatné cez ComfortPay

ComfortPay je CardPay, ktorý zaregistruje kartu. Prvá platba prejde stránkou banky ako každá platba cez CardPay a vráti
ID karty (`CID`); odvtedy váš server zaťažuje túto kartu cez SOAP webovú službu Tatra banky, napríklad raz mesačne
z cron jobu.

Zdrojový kód: [`examples/comfortpay-recurring/run.php`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/comfortpay-recurring/run.php) ·
spustíte ho príkazom `examples/ci/example.sh comfortpay-recurring`.

<figure class="ot-diagram">
  <img class="light-only" src="/diagrams/flow-comfortpay-sk-light.svg" alt="Sekvencia: purchase() s TPAY=Y presmeruje do banky, banka vráti RES=OK s ID karty CID, completePurchase() návrat overí a uloží CID; neskôr charge() zavolá SOAP webovú službu s CID a klientskym certifikátom a dostane stav transakcie 00" width="760" height="454">
  <img class="dark-only" src="/diagrams/flow-comfortpay-sk-dark.svg" alt="Sekvencia: purchase() s TPAY=Y presmeruje do banky, banka vráti RES=OK s ID karty CID, completePurchase() návrat overí a uloží CID; neskôr charge() zavolá SOAP webovú službu s CID a klientskym certifikátom a dostane stav transakcie 00" width="760" height="454">
</figure>

## Konfigurácia {#configuration}

<<< @/../examples/comfortpay-recurring/run.php#config

Platba s presmerovaním používa `mid` a `sharedSecret` (HMAC alebo AES; ComfortPay nepodporuje DES). Webová služba
používa `ws`, `terminalId` a váš klientsky certifikát. V testovacom režime sa na požiadavky na webovú službu odpovedá
lokálne a certifikát sa nečíta.

## 1. Registrácia karty prvou platbou {#_1-register-the-card-with-the-first-payment}

<<< @/../examples/comfortpay-recurring/run.php#register

`purchase()` pri ComfortPay vždy posiela `TPAY=Y` a `AREDIR=1`.

## 2. Uloženie ID karty {#_2-keep-the-card-id}

<<< @/../examples/comfortpay-recurring/run.php#return

Ukladajte iba `CID`. Maskované číslo (`getCc()`) môžete zákazníkovi zobraziť; číslo karty sa k vám nikdy nedostane.

## 3. Mesačné zaťaženie karty {#_3-charge-the-card-every-month}

<<< @/../examples/comfortpay-recurring/run.php#charge

`transactionId` je vaše jedinečné ID platby: podľa neho sa neskôr pýtate na výsledok a nikdy ho nepoužite znova (banka
odpovie `50054`, duplicitná transakcia). Platbu identifikujte variabilným aj špecifickým symbolom (`vs` a `ss`) alebo
cez `e2eReference`.

V testovacom režime sa karta s párnym ID zamietne (`transactionStatus` `02`) a s nepárnym schváli, takže si môžete
vyskúšať obe vetvy kódu; ID karty `111111` zo simulátora je nepárne.

## 4. Kontrola a správa karty {#_4-check-and-manage-the-card}

<<< @/../examples/comfortpay-recurring/run.php#manage

`listOfExpired(['expDate' => '2026-12-01'])` vráti zaregistrované karty, ktorým v danom mesiaci končí platnosť (podľa
WSDL sa deň ignoruje), takže týchto zákazníkov môžete požiadať o novú kartu skôr, než platba zlyhá.

## Výstup {#output}

<<< @/../examples/comfortpay-recurring/expected-output.txt{text}

## Čo príklad testuje {#what-it-tests}

- simulátor prijme požiadavku ComfortPay (s podpísaným `TPAY=Y`) a vráti ID zaregistrovanej karty, ktoré
  `completePurchase()` overí;
- `charge()` zostaví platnú požiadavku na webovú službu pre schválenú aj zamietnutú kartu;
- `checkTransaction()`, `checkCard()`, `listOfExpirePerId()` a `unRegisterCard()` vrátia typované výsledky.

V produkcii pozrite [Spracovanie chýb](/sk/guide/errors#comfortpay-web-service) pre zamietnutia, timeouty a SOAP
faulty a [TLS a proxy](/sk/guide/comfortpay#tls-and-proxy) pre nastavenie spojenia.
