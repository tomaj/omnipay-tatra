---
title: Configuration
description: Gateway parameters of TatraPay, CardPay and ComfortPay, and how the length of the shared secret selects the signature algorithm.
---

# Configuration

Set gateway parameters with setters or `initialize()`; they are passed to every request the gateway creates. Request
parameters go to `purchase([...])`, `completePurchase([...])` and the other request methods.

## Gateway parameters

| Parameter | Gateways | Description |
|---|---|---|
| `mid` | all | Merchant id (`MID`) from Tatra banka |
| `sharedSecret` | all | The key from Tatra banka; its length selects the [signature algorithm](#the-key-selects-the-algorithm) |
| `testMode` | all | Send requests to the simulator instead of the bank, see [Test mode](./test-mode) |
| `testHost` | all | Host of the test endpoints, default `https://platby.tomaj.sk` |
| `ws` | ComfortPay | Merchant id of the ComfortPay web service (`merchantId`) |
| `terminalId` | ComfortPay | Terminal id of the web service |
| `certPath` | ComfortPay | Path to the PEM file with your client certificate and private key |
| `certPass` | ComfortPay | Passphrase of the private key |
| `caFile` | ComfortPay | CA bundle that verifies the web service certificate instead of the system CA store |
| `streamContext` | ComfortPay | Your own `stream_context_create()` for the web service connection |
| `proxyHost`, `proxyPort`, `proxyLogin`, `proxyPassword` | ComfortPay | HTTP proxy for the web service |
| `soapClient` | ComfortPay | Your own `SoapClient` (custom transport, tests) |

## Request parameters

| Parameter | Bank field | Description |
|---|---|---|
| `amount` | `AMT` | Amount as a string with the currency's decimals, `'10.00'` |
| `currency` | `CURR` | ISO 4217 code, `'EUR'`; sent as the numeric code (`978`) |
| `vs` | `VS` | Variable symbol, up to 10 digits |
| `cs` | `CS` | Constant symbol, 4 digits |
| `ss` | `SS` | Specific symbol, up to 10 digits (TatraPay) |
| `rurl` | `RURL` | Return URL |
| `rem` | `REM` | E-mail address for the result notification |
| `rsms` | `RSMS` | Mobile number for an SMS notification |
| `desc` | `DESC` | Description |
| `aredir` | `AREDIR` | Redirect back automatically (`true`/`false`) |
| `lang` | `LANG` | Language of the bank's page: `sk`, `en`, `de`, `hu`, `cz`, ... |
| `name` | `NAME` | Customer name (CardPay, ComfortPay): letters without diacritics, digits, space and `. - _ @` |
| `ipc` | `IPC` | Customer IP address (CardPay, ComfortPay); `REMOTE_ADDR` when omitted |
| `timestamp` | `TIMESTAMP` | Set automatically in UTC (`ddmmYYYYHHMMSS`), signed with HMAC keys |

Setters take strings. `initialize()` and the request methods also accept integers and convert them, so
`'vs' => 123456` works.

## The key selects the algorithm

| Key length | Algorithm | Field | Notes |
|---|---|---|---|
| 128 hexadecimal characters | HMAC-SHA256 | `HMAC` | Current Tatra banka keys; needed for `authorize()` and `cancelAuthorize()` |
| 64 hexadecimal characters | "AES256" | `SIGN` | See [Signatures](./signatures#aes) |
| 8 characters | DES | `SIGN` | Legacy; needs the OpenSSL legacy provider; not for ComfortPay |

Any other length throws `Omnipay\Core\Exception\UnsupportedKeyException`. Endpoints differ by key too: HMAC requests
go to `https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/tatrapay` or `.../cardpay`, AES and DES requests to
`.../e-commerce.jsp`.

## Keep secrets out of the code

Load `sharedSecret`, `certPath` and `certPass` from the environment or a secret store; never commit them.

```php
$gateway->setSharedSecret((string) getenv('TATRA_SHARED_SECRET'));
```
