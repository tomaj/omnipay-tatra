---
title: API reference
description: Every public class and method of omnipay-tatra 5.x - gateways, requests, responses, signers, enums and exceptions.
---

# API reference

All classes are `final` unless marked abstract. Parameter getters return `?string` and setters take `?string` unless
stated otherwise; every request also has Omnipay's common accessors (`getAmount()`, `getCurrency()`,
`getTransactionId()`, `getTestMode()`, ...). `send()` returns the response class listed below.

## Gateways

| Class | `getName()` | Requests |
|---|---|---|
| `Omnipay\TatraPay\Gateway` | `TatraPay Gateway` | `purchase()`, `completePurchase()` |
| `Omnipay\CardPay\Gateway` | `CardPay Gateway` | `purchase()`, `completePurchase()`, `authorize()`, `completeAuthorize()`, `cancelAuthorize()` |
| `Omnipay\ComfortPay\Gateway` | `ComfortPay Gateway` | `purchase()`, `completePurchase()`, `charge()`, `checkCard()`, `checkTransaction()`, `listOfExpired()`, `listOfExpirePerId()`, `unRegisterCard()` |

Every gateway: `getMid()`/`setMid()`, `getSharedSecret()`/`setSharedSecret()`, `getTestHost()`/`setTestHost()`,
`getDefaultParameters()`. ComfortPay adds `getWs()`/`setWs()`, `getTerminalId()`/`setTerminalId()`,
`getCertPath()`/`setCertPath()`, `getCertPass()`/`setCertPass()`, `getCaFile()`/`setCaFile()`,
`getStreamContext()`/`setStreamContext(resource|null)`, `getProxyHost()`/`setProxyHost()`,
`getProxyPort(): ?int`/`setProxyPort(int|string|null)`, `getProxyLogin()`/`setProxyLogin()`,
`getProxyPassword()`/`setProxyPassword()`, `getSoapClient(): ?SoapClient`/`setSoapClient(?SoapClient)`.

## Requests

### `Omnipay\Core\Message\AbstractRequest` (abstract)

Base of every request: `getMid()`, `getSharedSecret()`, `getTestHost()`, `getVs()`, `getCs()`, `getSs()`, `getDesc()`,
`getRsms()`, `getLang()`, `getRem()`, `getRurl()`, `getTimestamp()` and their setters; `getAredir(): ?int` and
`setAredir(bool|int|string|null)`. Constant `DEFAULT_TEST_HOST = 'https://platby.tomaj.sk'`.

| Request | Extra methods | `send()` returns |
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

The ComfortPay requests use `Omnipay\ComfortPay\ParametersTrait` for the web service parameters (`ws`,
`terminalId`, `certPath`, `certPass`, `caFile`, `streamContext`, the proxy and `soapClient`, with the types listed for
the gateway above). The ComfortPay web service requests extend `ComfortPay\Message\AbstractSoapRequest` (abstract): constant `WSDL` (the
bundled `Teleplatba_1_0.wsdl`) and `getSoapClientOptions(): array`.

## Responses

| Response | Methods |
|---|---|
| `TatraPay\Message\PurchaseResponse`, `CardPay\Message\PurchaseResponse`, `CardPay\Message\AuthorizeResponse`, `ComfortPay\Message\PurchaseResponse` | extend `Core\Message\AbstractRedirectResponse`: `isRedirect()` `true`, `isSuccessful()` `false`, `getRedirectUrl()`, `getRedirectMethod()` (`GET`), `getRedirectData()`, `redirect()` |
| `TatraPay\Message\CompletePurchaseResponse` | `isSuccessful()`, `isPending()` (`TOUT`), `getRes()`, `getVs()` |
| `CardPay\Message\CompletePurchaseResponse` | `isSuccessful()`, `getRes()`, `getVs()`, `getRc()` |
| `CardPay\Message\CompleteAuthorizeResponse` | `isSuccessful()`, `getRes()`, `getVs()`, `getRc()`, `getTres()`, `getCid()`, `getCc()`, `getAc()`, `getTransactionReference()` (TID) |
| `CardPay\Message\CancelAuthorizeResponse` | `isSuccessful()`, `getRes()`, `getErrorCode()`, `getCode()` |
| `ComfortPay\Message\CompletePurchaseResponse` | `isSuccessful()`, `getRes()`, `getVs()`, `getCid()`, `getCc()`, `getTres()`, `getRc()` |
| `ComfortPay\Message\CardTransactionResponse` | `isSuccessful()` (`00`), `isPending()` (50001), `getTransactionId()`, `getTransactionStatus()`, `getTransactionApproval()` (each `string\|false`), `getErrorCode(): ?int`, `getCode()` |
| `ComfortPay\Message\CheckCardResponse` | `isSuccessful()`, `getStatus()`, `getCardStatus(): ?CardStatus` |
| `ComfortPay\Message\UnRegisterCardResponse` | `isSuccessful()` |
| `ComfortPay\Message\ListOfExpiredResponse`, `ListOfExpirePerIdResponse` | `isSuccessful()` (non-empty list), `getExpires(): array` |

The callback responses extend `Core\Message\AbstractCallbackResponse` (abstract, constant `SUCCESS = 'OK'`).

## Signers

`Omnipay\Core\Sign\SignInterface::sign(string $input, string $secret): string`, implemented by `HmacSign`,
`Aes256Sign` and `DesSign` (see [Signatures](/guide/signatures)). `Signature::equals(string $expected, mixed $received): bool`
compares in constant time.
The `$secret` of `sign()` and `KeyType::fromSecret()`, and the value of `setSharedSecret()`, `setCertPass()` and
`setProxyPassword()`, are marked `#[\SensitiveParameter]` ([Security](/security#secrets-in-error-reports)).

## Enums

| Enum | Cases |
|---|---|
| `Omnipay\Core\Sign\KeyType` (int) | `Des` (8), `Aes` (64), `Hmac` (128); `fromSecret(string): self`, `signer(): SignInterface` |
| `Omnipay\CardPay\Message\CancelAuthorizeTxn` (string) | `ClosePreauthorization` (`CPA`), `StornoPreauthorization` (`SPA`), `Chargeback` (`CB`) |
| `Omnipay\ComfortPay\TransactionType` (string) | `Purchase`, `Preauthorization`, `PreauthConfirm`, `PreauthCancel`, `Chargeback`; `requiresParentTransaction(): bool` |
| `Omnipay\ComfortPay\CardStatus` (string) | `Ok`, `Fail`, `Unknown` |

The 4.x constants `CancelAuthorizeRequest::TXN_*`, `ComfortPay\Gateway::TRANSACTION_TYPE_*` and `CARD_STATUS_*` still
exist with their 4.x values. They are marked `#[\Deprecated(since: '5.0')]`: reading one emits `E_USER_DEPRECATED`
naming the enum case to use. They are removed in 6.0.

## Constants

| Constant | Value |
|---|---|
| `Core\Message\AbstractRequest::DEFAULT_TEST_HOST` | `'https://platby.tomaj.sk'` |
| `Core\Message\AbstractCallbackResponse::SUCCESS` | `'OK'` |
| `TatraPay\Message\CompletePurchaseResponse::TIMEOUT` | `'TOUT'` |
| `CardPay\Message\AuthorizeRequest::TXN_VALUE` | `'PA'`, returned by `getTxn()` |
| `CardPay\Message\CancelAuthorizeResponse::SUCCESS` | `'OK'` |
| `ComfortPay\Message\CardTransactionResponse::STATUS_APPROVED` | `'00'` |
| `ComfortPay\Message\AbstractSoapRequest::WSDL` | path of the bundled `Teleplatba_1_0.wsdl` |
| `ComfortPay\Exception\ComfortPaySoapFault::GENERAL_ERROR` ... `PARENT_TRANSACTION_NOT_FOUND` | `50000` ... `50060`, see [ComfortPay](/guide/comfortpay#declines-and-errors) |

Enum values: `TransactionType::Purchase` is `'PURCHASE'`, `Preauthorization` `'PREAUTHORIZATION'`, `PreauthConfirm`
`'PREAUTH-CONFIRM'`, `PreauthCancel` `'PREAUTH-CANCEL'`, `Chargeback` `'CHARGEBACK'`; `requiresParentTransaction()`
is `true` for `PreauthConfirm`, `PreauthCancel` and `Chargeback`. `CardStatus::Ok` is `'OK'`, `Fail` `'FAIL'`,
`Unknown` `'UNKNOWN'`.

## Exceptions

Every exception thrown by the package implements `Omnipay\Core\Exception\TatraException`, except Omnipay's own
`Omnipay\Common\Exception\InvalidRequestException` and `InvalidResponseException`.

| Exception | Extends | Thrown when |
|---|---|---|
| `Core\Exception\UnsupportedKeyException` | `InvalidArgumentException` | the shared secret has an unsupported length (or DES for ComfortPay) |
| `Core\Exception\SignatureException` | `RuntimeException` | OpenSSL cannot compute the signature, or the HMAC/AES key is not hexadecimal |
| `Core\Exception\GatewayException` | `RuntimeException` | `cancelAuthorize()` gets an HTTP status other than 200 |
| `Core\Exception\InvalidParameterException` | `UnexpectedValueException` | unsupported `txn` or `transactionType` |
| `ComfortPay\Exception\ComfortPaySoapFault` | `SoapFault` | a SOAP fault from the web service; `getErrorCode(): ?int`, `isReceiverFault(): bool`, `static fromSoapFault(\SoapFault): self` |
| `InvalidRequestException` (Omnipay) | | missing or invalid parameter, wrong callback signature, variable symbol mismatch |
| `InvalidResponseException` (Omnipay) | | wrong signature or invalid XML in a response from the bank |
