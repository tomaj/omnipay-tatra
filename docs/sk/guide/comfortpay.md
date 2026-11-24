---
title: ComfortPay
description: ComfortPay s omnipay-tatra - registrácia karty počas platby CardPay a neskoršie strhnutie platby z karty cez webovú službu SOAP.
---

# ComfortPay

ComfortPay je CardPay, pri ktorom sa zaregistruje karta zákazníka (`TPAY=Y`). Banka vráti ID karty (`CID`) a s ním
môžete z karty neskôr strhnúť platbu cez webovú službu SOAP Tatra banky, napríklad pri opakovaných platbách.
Podporované sú kľúče HMAC a AES, DES nie.

<figure class="ot-diagram">
  <img class="light-only" src="/diagrams/flow-comfortpay-sk-light.svg" alt="Sekvencia: purchase() s TPAY=Y presmeruje zákazníka do banky, banka vráti RES=OK s ID karty CID, completePurchase() overí podpis a uloží CID; neskôr charge() zavolá webovú službu SOAP s CID a klientskym certifikátom" width="760" height="454">
  <img class="dark-only" src="/diagrams/flow-comfortpay-sk-dark.svg" alt="Sekvencia: purchase() s TPAY=Y presmeruje zákazníka do banky, banka vráti RES=OK s ID karty CID, completePurchase() overí podpis a uloží CID; neskôr charge() zavolá webovú službu SOAP s CID a klientskym certifikátom" width="760" height="454">
</figure>

::: tip Spustiteľný príklad
[Predplatné cez ComfortPay](/sk/examples/comfortpay-recurring) zaregistruje kartu v simulátore platobnej brány
payment-simulator a v testovacom režime z nej strhne platbu; beží v CI.
:::

## Registrácia karty: purchase() a completePurchase() {#register-the-card-purchase-and-completepurchase}

<<< @/snippets/comfortpay-purchase.php#purchase

<<< @/snippets/comfortpay-purchase.php#complete

`CompletePurchaseResponse` pridáva k `getRes()` a `getVs()` metódy `getCid()` (zaregistrovaná karta), `getCc()`
(maskované číslo karty), `getTres()` a `getRc()`.

## Webová služba {#the-web-service}

Požiadavky SOAP sa autentifikujú vaším klientskym certifikátom (`certPath`, `certPass`) a potrebujú `ws`
(identifikátor obchodníka) a pri `charge()` aj `terminalId`. TLS certifikát banky sa overuje; pozrite
[TLS a proxy](#tls-and-proxy).

### charge() {#charge}

<<< @/snippets/comfortpay-charge.php#charge

| Parameter | Povinný | Poznámka |
|---|---|---|
| `transactionId` | áno | vaše jedinečné ID transakcie |
| `transactionType` | áno | `TransactionType::Purchase`, `Preauthorization`, `PreauthConfirm`, `PreauthCancel`, `Chargeback` (alebo ich reťazcová hodnota) |
| `parentTransactionId` | pri `PreauthConfirm`, `PreauthCancel`, `Chargeback` | transakcia, na ktorú sa odkazuje |
| `referedCardId` | áno | zaregistrovaná karta (`CID`) |
| `amount`, `currency` | áno | |
| `vs` a `ss`, alebo `e2eReference` | áno | oba symboly, alebo E2E referencia, ak sú symboly prázdne |
| `submerchantId`, `location`, `city`, `alpha2CountryCode` | všetky alebo žiadny | údaje IPSP |

`CardTransactionResponse`:

| Metóda | Vracia |
|---|---|
| `isSuccessful()` | `true` pre `transactionStatus` `00` |
| `isPending()` | `true` pre chybový kód `50001` (timeout, výsledok zatiaľ nie je známy) |
| `getTransactionId()`, `getTransactionStatus()`, `getTransactionApproval()` | hodnoty od banky; `false` pri zamietnutej platbe |
| `getErrorCode()` | chybový kód banky pri zamietnutej platbe, pozrite nižšie |
| `getCode()` | chybový kód alebo stav transakcie |

### Zamietnutia a chyby {#declines-and-errors}

Tatra banka zamietne platbu chybou SOAP (`SOAP-ENV:Receiver`) s chybovým kódom. `charge()` z nej urobí neúspešnú
odpoveď s `getErrorCode()`; každá iná chyba SOAP a každá chyba ostatných požiadaviek sa vyhodí ako výnimka
`Omnipay\ComfortPay\Exception\ComfortPaySoapFault`. Tá dedí od `\SoapFault` a má metódu `getErrorCode()`.

| Kód | Konštanta (`ComfortPaySoapFault::`) | Význam |
|---|---|---|
| 50000 | `GENERAL_ERROR` | všeobecná chyba pri spracovaní transakcie |
| 50001 | `TIMEOUT` | timeout, výsledok zatiaľ nie je známy |
| 50051 | `UNKNOWN_CARD` | ID karty (CID) neexistuje |
| 50052 | `INVALID_MERCHANT` | nesprávny identifikátor obchodníka |
| 50053 | `NEGATIVE_AMOUNT` | záporná suma |
| 50054 | `DUPLICATE_TRANSACTION` | duplicitné ID transakcie |
| 50055 | `SYMBOL_TOO_LONG` | VS alebo SS je dlhší ako 10 číslic |
| 50056 | `E2E_TOO_LONG` | E2E referencia je dlhšia ako 35 znakov |
| 50057 | `INVALID_PARENT_TRANSACTION` | chyba v `parentTransactionId` |
| 50058 | `SYMBOL_NOT_NUMERIC` | VS alebo SS obsahuje iné znaky ako číslice |
| 50059 | `AMOUNT_EXCEEDS_AUTHORIZATION` | suma prevyšuje pôvodnú autorizáciu |
| 50060 | `PARENT_TRANSACTION_NOT_FOUND` | za posledných 365 dní neexistuje nadradená transakcia s týmto VS/E2E, typom, stavom a menou |

Stav transakcie pri zodpovedanej platbe je kód kartovej spoločnosti: `00` schválená, `05` nevyplácať, `14` neplatné
číslo karty, `51` nedostatok prostriedkov, `54` karta po platnosti, `57` transakcia nie je držiteľovi karty povolená,
`61` prekročený limit sumy, `04` zadržať kartu; záporná hodnota znamená, že transakcia ešte nie je dokončená.

### Správa zaregistrovaných kariet {#manage-registered-cards}

<<< @/snippets/comfortpay-cards.php#cards

| Požiadavka | Parametre | Odpoveď |
|---|---|---|
| `checkCard()` | `cardId` | `CheckCardResponse`: `getCardStatus()` (enum `CardStatus`), `getStatus()` (`OK`, `FAIL`, `UNKNOWN`) |
| `checkTransaction()` | `transactionId` | `CardTransactionResponse` |
| `listOfExpired()` | `expDate` (`YYYY-MM-DD`): karty, ktorým v danom mesiaci končí platnosť; deň sa ignoruje | `ListOfExpiredResponse::getExpires()`: `[['id' => ...]]` |
| `listOfExpirePerId()` | `cardIds` (najviac 1000) | `ListOfExpirePerIdResponse::getExpires()`: `[['id' => ..., 'date' => 'MMYY']]` |
| `unRegisterCard()` | `cardId` | `UnRegisterCardResponse::isSuccessful()` |

### TLS a proxy {#tls-and-proxy}

<<< @/snippets/comfortpay-tls.php#tls

`getSoapClientOptions()` na ktorejkoľvek požiadavke webovej služby ukáže nastavenia, s ktorými sa vytvorí `SoapClient`.
