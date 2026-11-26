---
title: Spracovanie chýb
description: Každá výnimka, ktorú omnipay-tatra vyhadzuje, kedy vzniká a ako ju spracovať - neplatné parametre, podvrhnuté callbacky, nepodporované kľúče, chyby banky a chyby SOAP pri ComfortPay.
---

# Spracovanie chýb

Balík rozlišuje tri druhy zlyhania:

- **banka povedala nie**: bežná neúspešná odpoveď (`RES=FAIL`, zamietnutá platba ComfortPay, `res` `FAIL` pri
  dokončení predautorizácie). Nič sa nevyhadzuje; skontrolujte `isSuccessful()`.
- **vstupu sa nedá veriť alebo nie je platný**: výnimka Omnipay `InvalidRequestException` (parameter chýba alebo je
  neplatný, callback je podvrhnutý alebo patrí inej objednávke) a `InvalidResponseException` (server-to-server
  odpoveď banky nie je správne podpísaná alebo nie je XML).
- **problém s konfiguráciou alebo komunikáciou**: výnimka, ktorá implementuje `Omnipay\Core\Exception\TatraException`.

## Všetky výnimky {#every-exception}

| Výnimka | Dedí od | Vyhadzuje | Kedy |
|---|---|---|---|
| `Omnipay\Common\Exception\InvalidRequestException` | `Exception` | každá požiadavka | chýba povinný parameter (`The vs parameter is required`), `name` obsahuje znaky, ktoré banka odmietne, chýba `currency` |
| | | `completePurchase()`, `completeAuthorize()` | `Variable symbol mismatch`: banka vrátila iný `VS`, ako je `vs`, ktorý odovzdávate |
| | | `completePurchase()`, `completeAuthorize()` | `incorect signature` (CardPay HMAC: `incorrect signature for data: '...'`): `HMAC`/`SIGN` sa nezhoduje |
| `Omnipay\Common\Exception\InvalidResponseException` | `Exception` | `cancelAuthorize()` | `Incorrect signature` odpovede XML, alebo odpoveď nie je XML |
| | | `listOfExpired()`, `listOfExpirePerId()` | odpoveď webovej služby nie je XML |
| `Omnipay\Core\Exception\UnsupportedKeyException` | `InvalidArgumentException` | každá podpisovaná požiadavka | zdieľaný kľúč nemá 8, 64 ani 128 znakov, alebo kľúč DES pri ComfortPay |
| `Omnipay\Core\Exception\SignatureException` | `RuntimeException` | každá podpisovaná požiadavka | OpenSSL nevie vypočítať podpis, typicky DES bez legacy providera, alebo kľúč HMAC/AES nie je hexadecimálny |
| `Omnipay\Core\Exception\GatewayException` | `RuntimeException` | `cancelAuthorize()` | banka odpovedala iným HTTP stavom ako 200 |
| `Omnipay\Core\Exception\InvalidParameterException` | `UnexpectedValueException` | `cancelAuthorize()`, `charge()` | `txn` iný ako `CPA`, `SPA`, `CB`, alebo neznámy `transactionType` |
| `Omnipay\ComfortPay\Exception\ComfortPaySoapFault` | `SoapFault` | každá požiadavka webovej služby ComfortPay | chyba SOAP, okrem zamietnutej platby cez `charge()` (pozrite [nižšie](#comfortpay-web-service)) |

`UnsupportedKeyException`, `SignatureException`, `GatewayException`, `InvalidParameterException` aj
`ComfortPaySoapFault` implementujú `Omnipay\Core\Exception\TatraException`, takže jeden `catch` zachytí každý problém,
ktorý sa netýka vstupu. `InvalidRequestException` z [kontroly vs](/sk/upgrade#tatrapay-completepurchase-requires-vs)
a z kontroly podpisu je tá, ktorú musíte brať ako možný útok.

## Na návratovej URL {#on-the-return-url}

```php
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Core\Exception\TatraException;

try {
    $response = $gateway->completePurchase(['vs' => $order->vs, 'amount' => $order->amount, 'currency' => 'EUR'])->send();
} catch (InvalidRequestException $e) {
    // forged, edited or foreign callback: change nothing, log it, answer 400
    $status = 'rejected';
} catch (TatraException $e) {
    // wrong key configured, OpenSSL without DES: a bug on your side, alert
    $status = 'error';
}
```

Správu výnimky zákazníkovi nezobrazujte; správa pri CardPay obsahuje podpisované údaje. Neskúšajte to znova: ten
istý query string zlyhá rovnako.

`RES=FAIL` nie je výnimka: `isSuccessful()` je `false` a `getRes()` povie prečo. Pri TatraPay je `isPending()` `true`
pre `RES=TOUT`: banka ešte nerozhodla a výsledok pošle e-mailom na `rem`; objednávku nechajte otvorenú.

## Dokončenie, zrušenie predautorizácie a vrátenie platby {#capture-release-and-refund}

`cancelAuthorize()` posiela server-to-server požiadavku:

| Výsledok | Čo dostanete |
|---|---|
| banka to vykonala | `isSuccessful()` `true`, `getRes()` `OK` |
| banka odmietla | `isSuccessful()` `false`, `getRes()` `FAIL`, `getErrorCode()` (aj `getCode()`) je chybový kód banky |
| HTTP stav iný ako 200 | `GatewayException` so stavom v správe |
| nesprávny `HMAC` v hlavičke `Authorization` alebo chýbajúce XML | `InvalidResponseException` |
| sieťová chyba | výnimka Omnipay `Omnipay\Common\Http\Exception\NetworkException` (spojenie) alebo `RequestException` (čokoľvek iné, čo vyhodí HTTP klient) |

Odpoveď `FAIL` bez hlavičky `Authorization` sa prijme tak, ako je (banka nepodpisuje každú chybovú odpoveď):
`FAIL` berte ako „nevykonané“, nikdy ako dôkaz, že sa niečo vykonalo.

<<< @/../examples/cardpay-preauthorization/run.php#wrong-key

## Webová služba ComfortPay {#comfortpay-web-service}

Tatra banka zamietne platbu chybou SOAP (`SOAP-ENV:Receiver`) s chybovým kódom. `charge()` z nej urobí neúspešnú
`CardTransactionResponse` s `getErrorCode()`; každá iná chyba a každá chyba ostatných požiadaviek webovej služby sa
vyhodí ako `ComfortPaySoapFault` s metódami `getErrorCode(): ?int` a `isReceiverFault(): bool`.

```php
use Omnipay\ComfortPay\Exception\ComfortPaySoapFault;

try {
    $charge = $comfortPay->charge($chargeParameters)->send();
} catch (ComfortPaySoapFault $fault) {
    // the service failed: retry later with the same transactionId, or check it with checkTransaction()
    $code = $fault->getErrorCode();
}
```

| `CardTransactionResponse` | Význam | Čo robiť |
|---|---|---|
| `isSuccessful()` | `transactionStatus` `00` | hotovo |
| `isPending()` | chybový kód `50001` | platbu **nestrhávajte** znova: neskôr sa opýtajte cez `checkTransaction()` |
| `getErrorCode()` nie je `null` | banka zamietla, pozrite [chybové kódy](./comfortpay#declines-and-errors) | neopakujte `50051` (neznáma karta) ani `50054` (duplicita) |
| `getTransactionStatus()` iný ako `00` | zamietla kartová spoločnosť (`05`, `51`, `54` ...) | požiadajte zákazníka o inú kartu |

## Pred presmerovaním {#before-the-redirect}

`purchase()` a `authorize()` overia vstup skôr, ako čokoľvek opustí váš server: chýbajúci `vs`, `rurl`, `currency`
(alebo `name` a `ipc` pri CardPay) a `name` s diakritikou vyhodia `InvalidRequestException`. Riešte ich ako chybu vo
vašom košíku, nie ako zlyhanie platby.
