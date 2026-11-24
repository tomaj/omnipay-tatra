---
title: Referencia API
description: Všetky verejné triedy a metódy omnipay-tatra 5.x - brány, requesty, odpovede, podpisovače, enumy a výnimky.
---

# Referencia API

Všetky triedy sú `final`, ak nie sú označené ako abstraktné. Gettery parametrov vracajú `?string` a settery berú
`?string`, ak nie je uvedené inak; každý request má aj spoločné metódy Omnipay (`getAmount()`, `getCurrency()`,
`getTransactionId()`, `getTestMode()`, ...). `send()` vracia triedu odpovede uvedenú nižšie.

## Brány {#gateways}

| Trieda | `getName()` | Requesty |
|---|---|---|
| `Omnipay\TatraPay\Gateway` | `TatraPay Gateway` | `purchase()`, `completePurchase()` |
| `Omnipay\CardPay\Gateway` | `CardPay Gateway` | `purchase()`, `completePurchase()`, `authorize()`, `completeAuthorize()`, `cancelAuthorize()` |
| `Omnipay\ComfortPay\Gateway` | `ComfortPay Gateway` | `purchase()`, `completePurchase()`, `charge()`, `checkCard()`, `checkTransaction()`, `listOfExpired()`, `listOfExpirePerId()`, `unRegisterCard()` |

Každá brána: `getMid()`/`setMid()`, `getSharedSecret()`/`setSharedSecret()`, `getTestHost()`/`setTestHost()`,
`getDefaultParameters()`. ComfortPay navyše pridáva `getWs()`/`setWs()`, `getTerminalId()`/`setTerminalId()`,
`getCertPath()`/`setCertPath()`, `getCertPass()`/`setCertPass()`, `getCaFile()`/`setCaFile()`,
`getStreamContext()`/`setStreamContext(resource|null)`, `getProxyHost()`/`setProxyHost()`,
`getProxyPort(): ?int`/`setProxyPort(int|string|null)`, `getProxyLogin()`/`setProxyLogin()`,
`getProxyPassword()`/`setProxyPassword()`, `getSoapClient(): ?SoapClient`/`setSoapClient(?SoapClient)`.

## Requesty {#requests}

### `Omnipay\Core\Message\AbstractRequest` (abstraktná) {#omnipay-core-message-abstractrequest-abstract}

Základ každého requestu: `getMid()`, `getSharedSecret()`, `getTestHost()`, `getVs()`, `getCs()`, `getSs()`, `getDesc()`,
`getRsms()`, `getLang()`, `getRem()`, `getRurl()`, `getTimestamp()` a ich settery; `getAredir(): ?int` a
`setAredir(bool|int|string|null)`. Konštanta `DEFAULT_TEST_HOST = 'https://platby.tomaj.sk'`.

| Request | Ďalšie metódy | `send()` vracia |
|---|---|---|
| `TatraPay\Message\PurchaseRequest` | `getEndpoint(): string`, `generateSignature(string): string` | `TatraPay\Message\PurchaseResponse` |
| `TatraPay\Message\CompletePurchaseRequest` | | `TatraPay\Message\CompletePurchaseResponse` |
| `CardPay\Message\PurchaseRequest` | `getIpc()`, `getName()`, `getEndpoint()`, `generateSignature()` | `CardPay\Message\PurchaseResponse` |
| `CardPay\Message\CompletePurchaseRequest` | | `CardPay\Message\CompletePurchaseResponse` |
| `CardPay\Message\AuthorizeRequest` | `getIpc()`, `getName()`, `getTpay()`, `getTxn(): string` (`PA`), `getE2E()`, `getCid()`, `getEcid()`, `getTdsCardholder()`, `getTdsEmail()`, `getTdsMobilePhone()`, `getTdsBill*()`, `getTdsShip*()`, `getTdsAddrMatch()`, `getEndpoint()`, `generateSignature()` | `CardPay\Message\AuthorizeResponse` |
| `CardPay\Message\CompleteAuthorizeRequest` | | `CardPay\Message\CompleteAuthorizeResponse` |
| `CardPay\Message\CancelAuthorizeRequest` | `getTid()`, `getTxn()`, `setTxn(CancelAuthorizeTxn\|string)`, `getEndpoint()` | `CardPay\Message\CancelAuthorizeResponse` |
| `ComfortPay\Message\PurchaseRequest` | `getIpc()`, `getName()`, `getTpay()` (`Y`), `getEndpoint()`, `generateSignature()` | `ComfortPay\Message\PurchaseResponse` |
| `ComfortPay\Message\CompletePurchaseRequest` | | `ComfortPay\Message\CompletePurchaseResponse` |
| `ComfortPay\Message\ChargeRequest` | `getTransactionType()`, `setTransactionType(TransactionType\|string\|null)`, `getParentTransactionId()`, `getReferedCardId()`, `getE2eReference()`, `getSubmerchantId()`, `getLocation()`, `getCity()`, `getAlpha2CountryCode()` | `ComfortPay\Message\CardTransactionResponse` |
| `ComfortPay\Message\TransactionStatusRequest` | | `ComfortPay\Message\CardTransactionResponse` |
| `ComfortPay\Message\CheckCardRequest` | `getCardId()` | `ComfortPay\Message\CheckCardResponse` |
| `ComfortPay\Message\UnRegisterCardRequest` | `getCardId()` | `ComfortPay\Message\UnRegisterCardResponse` |
| `ComfortPay\Message\ListOfExpiredRequest` | `getExpDate()` | `ComfortPay\Message\ListOfExpiredResponse` |
| `ComfortPay\Message\ListOfExpirePerIdRequest` | `getCardIds(): ?list<string>`, `setCardIds(?array)` | `ComfortPay\Message\ListOfExpirePerIdResponse` |

Requesty ComfortPay používajú pre parametre webovej služby `Omnipay\ComfortPay\ParametersTrait` (`ws`,
`terminalId`, `certPath`, `certPass`, `caFile`, `streamContext`, proxy a `soapClient`, s typmi uvedenými pri bráne
vyššie). Requesty na webovú službu ComfortPay rozširujú `ComfortPay\Message\AbstractSoapRequest` (abstraktná):
konštanta `WSDL` (pribalený `Teleplatba_1_0.wsdl`) a `getSoapClientOptions(): array`.

## Odpovede {#responses}

| Odpoveď | Metódy |
|---|---|
| `TatraPay\Message\PurchaseResponse`, `CardPay\Message\PurchaseResponse`, `CardPay\Message\AuthorizeResponse`, `ComfortPay\Message\PurchaseResponse` | rozširujú `Core\Message\AbstractRedirectResponse`: `isRedirect()` `true`, `isSuccessful()` `false`, `getRedirectUrl()`, `getRedirectMethod()` (`GET`), `getRedirectData()`, `redirect()` |
| `TatraPay\Message\CompletePurchaseResponse` | `isSuccessful()`, `isPending()` (`TOUT`), `getRes()`, `getVs()` |
| `CardPay\Message\CompletePurchaseResponse` | `isSuccessful()`, `getRes()`, `getVs()`, `getRc()` |
| `CardPay\Message\CompleteAuthorizeResponse` | `isSuccessful()`, `getRes()`, `getVs()`, `getRc()`, `getTres()`, `getCid()`, `getCc()`, `getAc()`, `getTransactionReference()` (TID) |
| `CardPay\Message\CancelAuthorizeResponse` | `isSuccessful()`, `getRes()`, `getErrorCode()`, `getCode()` |
| `ComfortPay\Message\CompletePurchaseResponse` | `isSuccessful()`, `getRes()`, `getVs()`, `getCid()`, `getCc()`, `getTres()`, `getRc()` |
| `ComfortPay\Message\CardTransactionResponse` | `isSuccessful()` (`00`), `isPending()` (50001), `getTransactionId()`, `getTransactionStatus()`, `getTransactionApproval()` (každá `string\|false`), `getErrorCode(): ?int`, `getCode()` |
| `ComfortPay\Message\CheckCardResponse` | `isSuccessful()`, `getStatus()`, `getCardStatus(): ?CardStatus` |
| `ComfortPay\Message\UnRegisterCardResponse` | `isSuccessful()` |
| `ComfortPay\Message\ListOfExpiredResponse`, `ListOfExpirePerIdResponse` | `isSuccessful()` (neprázdny zoznam), `getExpires(): array` |

Odpovede na návrat z banky (callback) rozširujú `Core\Message\AbstractCallbackResponse` (abstraktná, konštanta
`SUCCESS = 'OK'`).

## Podpisovače {#signers}

`Omnipay\Core\Sign\SignInterface::sign(string $input, string $secret): string`, implementovaný triedami `HmacSign`,
`Aes256Sign` a `DesSign` (pozrite [Podpisy](/sk/guide/signatures)). `Signature::equals(string $expected, mixed $received): bool`
porovnáva v konštantnom čase.

## Enumy {#enums}

| Enum | Hodnoty |
|---|---|
| `Omnipay\Core\Sign\KeyType` (int) | `Des` (8), `Aes` (64), `Hmac` (128); `fromSecret(string): self`, `signer(): SignInterface` |
| `Omnipay\CardPay\Message\CancelAuthorizeTxn` (string) | `ClosePreauthorization` (`CPA`), `StornoPreauthorization` (`SPA`), `Chargeback` (`CB`) |
| `Omnipay\ComfortPay\TransactionType` (string) | `Purchase`, `Preauthorization`, `PreauthConfirm`, `PreauthCancel`, `Chargeback`; `requiresParentTransaction(): bool` |
| `Omnipay\ComfortPay\CardStatus` (string) | `Ok`, `Fail`, `Unknown` |

Konštanty zo 4.x `CancelAuthorizeRequest::TXN_*`, `ComfortPay\Gateway::TRANSACTION_TYPE_*` a `CARD_STATUS_*` stále
existujú, sú však označené ako zastarané (deprecated).

## Konštanty {#constants}

| Konštanta | Hodnota |
|---|---|
| `Core\Message\AbstractRequest::DEFAULT_TEST_HOST` | `'https://platby.tomaj.sk'` |
| `Core\Message\AbstractCallbackResponse::SUCCESS` | `'OK'` |
| `TatraPay\Message\CompletePurchaseResponse::TIMEOUT` | `'TOUT'` |
| `CardPay\Message\AuthorizeRequest::TXN_VALUE` | `'PA'`, vracia ju `getTxn()` |
| `CardPay\Message\CancelAuthorizeResponse::SUCCESS` | `'OK'` |
| `ComfortPay\Message\CardTransactionResponse::STATUS_APPROVED` | `'00'` |
| `ComfortPay\Message\AbstractSoapRequest::WSDL` | cesta k pribalenému `Teleplatba_1_0.wsdl` |
| `ComfortPay\Exception\ComfortPaySoapFault::GENERAL_ERROR` ... `PARENT_TRANSACTION_NOT_FOUND` | `50000` ... `50060`, pozrite [ComfortPay](/sk/guide/comfortpay#declines-and-errors) |

Hodnoty enumov: `TransactionType::Purchase` je `'PURCHASE'`, `Preauthorization` `'PREAUTHORIZATION'`, `PreauthConfirm`
`'PREAUTH-CONFIRM'`, `PreauthCancel` `'PREAUTH-CANCEL'`, `Chargeback` `'CHARGEBACK'`; `requiresParentTransaction()`
je `true` pre `PreauthConfirm`, `PreauthCancel` a `Chargeback`. `CardStatus::Ok` je `'OK'`, `Fail` `'FAIL'`,
`Unknown` `'UNKNOWN'`.

## Výnimky {#exceptions}

Každá výnimka, ktorú balík vyhodí, implementuje `Omnipay\Core\Exception\TatraException`, okrem vlastných výnimiek
Omnipay `Omnipay\Common\Exception\InvalidRequestException` a `InvalidResponseException`.

| Výnimka | Rozširuje | Vyhodí sa, keď |
|---|---|---|
| `Core\Exception\UnsupportedKeyException` | `InvalidArgumentException` | kľúč má nepodporovanú dĺžku (alebo je to DES kľúč pre ComfortPay) |
| `Core\Exception\SignatureException` | `RuntimeException` | OpenSSL nevie vypočítať podpis |
| `Core\Exception\GatewayException` | `RuntimeException` | `cancelAuthorize()` dostane iný HTTP status ako 200 |
| `Core\Exception\InvalidParameterException` | `UnexpectedValueException` | nepodporovaný `txn` alebo `transactionType` |
| `ComfortPay\Exception\ComfortPaySoapFault` | `SoapFault` | SOAP fault z webovej služby; `getErrorCode(): ?int`, `isReceiverFault(): bool`, `static fromSoapFault(\SoapFault): self` |
| `InvalidRequestException` (Omnipay) | | chýbajúci alebo neplatný parameter, nesprávny podpis návratu z banky, nezhoda variabilného symbolu |
| `InvalidResponseException` (Omnipay) | | nesprávny podpis alebo neplatné XML v odpovedi banky |
