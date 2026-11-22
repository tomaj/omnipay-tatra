---
title: ComfortPay
description: ComfortPay with omnipay-tatra - register a card during a CardPay payment and charge it later through the SOAP web service.
---

# ComfortPay

ComfortPay is CardPay that registers the customer's card (`TPAY=Y`). The bank returns the card id (`CID`); with it you
charge the card later through Tatra banka's SOAP web service, for recurring payments for example. HMAC and AES keys are
supported, DES is not.

## Register the card: purchase() and completePurchase()

<<< @/snippets/comfortpay-purchase.php#purchase

<<< @/snippets/comfortpay-purchase.php#complete

`CompletePurchaseResponse` adds `getCid()` (registered card), `getCc()` (masked card number), `getTres()` and `getRc()`
to `getRes()` and `getVs()`.

## The web service

The SOAP requests authenticate with your client certificate (`certPath`, `certPass`) and need `ws` (merchant id) and,
for `charge()`, `terminalId`. The bank's TLS certificate is verified; see [TLS and proxy](#tls-and-proxy).

### charge()

<<< @/snippets/comfortpay-charge.php#charge

| Parameter | Required | Notes |
|---|---|---|
| `transactionId` | yes | your unique id of the transaction |
| `transactionType` | yes | `TransactionType::Purchase`, `Preauthorization`, `PreauthConfirm`, `PreauthCancel`, `Chargeback` (or the string value) |
| `parentTransactionId` | for `PreauthConfirm`, `PreauthCancel`, `Chargeback` | the transaction it refers to |
| `referedCardId` | yes | the registered card (`CID`) |
| `amount`, `currency` | yes | |
| `vs` and `ss`, or `e2eReference` | yes | both symbols, or the E2E reference when the symbols are empty |
| `submerchantId`, `location`, `city`, `alpha2CountryCode` | all or none | IPSP data |

`CardTransactionResponse`:

| Method | Returns |
|---|---|
| `isSuccessful()` | `true` for `transactionStatus` `00` |
| `isPending()` | `true` for error code `50001` (timeout, the result is not known yet) |
| `getTransactionId()`, `getTransactionStatus()`, `getTransactionApproval()` | the bank's values; `false` for a declined charge |
| `getErrorCode()` | the bank's error code of a declined charge, see below |
| `getCode()` | the error code, or the transaction status |

### Declines and errors

Tatra banka declines a charge with a SOAP fault (`SOAP-ENV:Receiver`) that carries an error code. `charge()` turns it
into an unsuccessful response with `getErrorCode()`; every other fault, and every fault of the other requests, is thrown
as `Omnipay\ComfortPay\Exception\ComfortPaySoapFault`. It extends `\SoapFault` and has `getErrorCode()`.

| Code | Constant (`ComfortPaySoapFault::`) | Meaning |
|---|---|---|
| 50000 | `GENERAL_ERROR` | general error while processing the transaction |
| 50001 | `TIMEOUT` | timeout, the result is not known yet |
| 50051 | `UNKNOWN_CARD` | the card id (CID) does not exist |
| 50052 | `INVALID_MERCHANT` | incorrect merchant id |
| 50053 | `NEGATIVE_AMOUNT` | negative amount |
| 50054 | `DUPLICATE_TRANSACTION` | duplicate transaction id |
| 50055 | `SYMBOL_TOO_LONG` | VS or SS longer than 10 digits |
| 50056 | `E2E_TOO_LONG` | E2E reference longer than 35 characters |
| 50057 | `INVALID_PARENT_TRANSACTION` | error in `parentTransactionId` |
| 50058 | `SYMBOL_NOT_NUMERIC` | VS or SS contains other characters than digits |
| 50059 | `AMOUNT_EXCEEDS_AUTHORIZATION` | the amount exceeds the original authorization |
| 50060 | `PARENT_TRANSACTION_NOT_FOUND` | no parent transaction with this VS/E2E, type, state and currency in the last 365 days |

The transaction status of an answered charge is the card network's code: `00` approved, `05` do not honour, `14`
invalid card number, `51` insufficient funds, `54` expired card, `57` not allowed for the cardholder, `61` amount limit
exceeded, `04` pick up the card; a negative value means the transaction is not finished yet.

### Manage registered cards

<<< @/snippets/comfortpay-cards.php#cards

| Request | Parameters | Response |
|---|---|---|
| `checkCard()` | `cardId` | `CheckCardResponse`: `getCardStatus()` (`CardStatus` enum), `getStatus()` (`OK`, `FAIL`, `UNKNOWN`) |
| `checkTransaction()` | `transactionId` | `CardTransactionResponse` |
| `listOfExpired()` | `expDate` (`YYYY-MM-DD`) | `ListOfExpiredResponse::getExpires()`: `[['id' => ...]]` |
| `listOfExpirePerId()` | `cardIds` (up to 1000) | `ListOfExpirePerIdResponse::getExpires()`: `[['id' => ..., 'date' => 'MMYY']]` |
| `unRegisterCard()` | `cardId` | `UnRegisterCardResponse::isSuccessful()` |

### TLS and proxy

<<< @/snippets/comfortpay-tls.php#tls

`getSoapClientOptions()` on any web service request shows the options the `SoapClient` is created with.
