# Change Log
All notable changes to this project will be documented in this file.
Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [Unreleased][unreleased]

### Added

* Documentation site at https://tomaj.github.io/omnipay-tatra/: getting started, configuration, TatraPay, CardPay and ComfortPay guides, test mode, the exact signed fields of every message, API reference and FAQ. Every example is a script in `docs/snippets/` that the test suite runs ([#31](https://github.com/tomaj/omnipay-tatra/issues/31), [#6](https://github.com/tomaj/omnipay-tatra/issues/6)).
* Documentation site at presentation level: own logo and illustrations, payment flow diagrams, error handling, testing and security pages, and six runnable examples in `examples/` (TatraPay, CardPay with HMAC and AES, pre-authorization with capture and refund, a ComfortPay subscription, a shop's return URL, signatures checked with `openssl`) that the Examples workflow runs against payment-simulator; the inline code of README.md, UPGRADE-5.0.md and the docs runs in the test suite ([#39](https://github.com/tomaj/omnipay-tatra/issues/39)).
* TatraPay `CompletePurchaseResponse::isPending()` is true for the timeout result `RES=TOUT`.
* ComfortPay: the bank's error code from a SOAP fault. A declined `charge()` (a `SOAP-ENV:Receiver` fault) returns an unsuccessful `CardTransactionResponse` with `getErrorCode()` (for example `50051`, unknown card) and `getCode()`; `isPending()` is true for `50001` (timeout, result not known yet). Other faults, and faults of the other web service requests, are thrown as `ComfortPaySoapFault`, which extends `\SoapFault` and has `getErrorCode()`; constants name the documented codes. Based on [#18](https://github.com/tomaj/omnipay-tatra/pull/18) ([#12](https://github.com/tomaj/omnipay-tatra/issues/12)).
* ComfortPay: `proxyHost`, `proxyPort`, `proxyLogin` and `proxyPassword` parameters route the web service requests through an HTTP proxy. Based on [#22](https://github.com/tomaj/omnipay-tatra/pull/22) by [@deltaglow](https://github.com/deltaglow).
* ComfortPay: `soapClient` parameter to pass your own `SoapClient` (a custom transport, or a fake one in tests).
- Slovak translation of the documentation site ([#45](https://github.com/tomaj/omnipay-tatra/issues/45)).

### Changed

* **BREAKING CHANGE**: Requires PHP 8.4 or newer (`"php": "^8.4"`; there was no PHP constraint before and CI covered PHP 8.0 - 8.3). Stay on 4.x for older PHP ([#28](https://github.com/tomaj/omnipay-tatra/issues/28)).
* CI runs in GitHub Actions: tests on PHP 8.4 (also with `--prefer-lowest`), 8.5 and 8.6 and a nightly PHP 8.7 job that may fail, 95 % statement coverage required, Infection, PHP-CS-Fixer (PER-CS, replaces PHP_CodeSniffer), PHPStan, Rector and `composer audit`; CodeQL and OpenSSF Scorecard workflows; tag-driven releases with build provenance. Dependabot keeps Composer packages and actions up to date ([#24](https://github.com/tomaj/omnipay-tatra/pull/24), [#26](https://github.com/tomaj/omnipay-tatra/issues/26)).
* **BREAKING CHANGE**: Native parameter and return types on every accessor and message method: setters take `?string` (pass strings when you call them directly from strict-types code; `initialize()` and the request factories still accept integers), getters return `?string`, `SignInterface::sign(string $input, string $secret): string`, and `send()` returns the concrete response class. See [UPGRADE-5.0.md](UPGRADE-5.0.md) ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* **BREAKING CHANGE**: Gateways, requests, responses and signers are `final`; extend through Omnipay's interfaces instead ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* **BREAKING CHANGE**: `completePurchase()` and `completeAuthorize()` read the bank's parameters from the gateway's HTTP request (`Request::createFromGlobals()` by default) instead of `$_GET`; a custom request passed to the gateway must carry the query string ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* **BREAKING CHANGE**: Variable symbols returned by the bank are compared strictly (`0123` no longer matches `123`) in CardPay and ComfortPay `completePurchase()` and CardPay `completeAuthorize()` ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* **BREAKING CHANGE**: Every exception the package throws implements `Omnipay\Core\Exception\TatraException` (besides Omnipay's `InvalidRequestException`/`InvalidResponseException`): `UnsupportedKeyException` replaces `\Exception('Unknown key length')`, `SignatureException` the `\RuntimeException` of the signers, `GatewayException` the `\Exception` of a failed cancel-authorization request, `InvalidParameterException` the `\UnexpectedValueException` of an unsupported TXN. ComfortPay `charge()` rejects an unknown `transactionType` before calling the bank ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* **BREAKING CHANGE**: CardPay `purchase()`/`authorize()` and ComfortPay `purchase()` reject a customer `name` with characters the bank does not accept (anything but letters without diacritics, digits, space, `.`, `-`, `_`, `@`) with an `InvalidRequestException`, instead of sending the customer to a bank error page. Transliterate the name before you pass it ([#3](https://github.com/tomaj/omnipay-tatra/issues/3)).
* Enums `CardPay\Message\CancelAuthorizeTxn`, `ComfortPay\TransactionType`, `ComfortPay\CardStatus` and `Core\Sign\KeyType` replace the constant sets and the key-length checks; the constants are deprecated ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* Test suite on PHPUnit 13 with every strict flag (fails on deprecations, notices and warnings): known signature vectors computed with the `openssl` CLI, the ComfortPay SOAP requests through a `SoapClient` on the bundled WSDL with a fake transport, the CardPay server-to-server requests with a mock HTTP client. Statement coverage 99 %, Infection with `minMsi`/`minCoveredMsi` 95 ([#30](https://github.com/tomaj/omnipay-tatra/issues/30)).
* PHPStan runs at level max with bleedingEdge, strict, deprecation and PHPUnit rules; PHP-CS-Fixer enforces `declare(strict_types=1)` and the PHP 8.4 migration rules; Rector checks the code; new composer scripts `rector`, `rector-fix`, `coverage` and `infection` ([#27](https://github.com/tomaj/omnipay-tatra/issues/27)).
* The Composer dist archive contains only the library (`src/`, `composer.json`, README, LICENSE, CHANGELOG); the ad-hoc scripts `index.php`, `test.php` and `testserver.php` were removed. Added `LICENSE.md` (MIT), `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, `SECURITY.md` and `RELEASING.md`; `omnipay/common` is required as `^3.4`, its first release for PHP 8.4 ([#25](https://github.com/tomaj/omnipay-tatra/issues/25)).

### Deprecated

* The 4.x constants `CardPay\Message\CancelAuthorizeRequest::TXN_*`, `ComfortPay\Gateway::TRANSACTION_TYPE_*` and `ComfortPay\Gateway::CARD_STATUS_*` carry PHP's native `#[\Deprecated]` attribute: reading one emits `E_USER_DEPRECATED` (`Constant ... is deprecated since 5.0, use ...` naming the enum case), so the deprecation shows up in logs and in test suites that fail on deprecations. They keep their values and are removed in 6.0 ([#48](https://github.com/tomaj/omnipay-tatra/issues/48)).

### Fixed

* TatraPay `purchase()` signed the specific symbol (`ss`) but did not send it, so the bank could not verify the signature of a payment with a specific symbol. `SS` is sent now, and the AES/DES callback signature includes it (`VS`, `SS`, `RES`) like the HMAC one, as Tatra banka's *Technická príručka k službe TatraPay* (version 3.3) specifies. The `tatrapay` example pays with a specific symbol on an HMAC and an AES key against payment-simulator, which signs its AES/DES return the same way since [tomaj/payment-simulator#34](https://github.com/tomaj/payment-simulator/issues/34).
* CardPay `authorize()`: `tdsCardholder` overwrote the `E2E` parameter, and `TDS_SHIP_CITY` was signed but not sent, so the bank rejected the signature when it was set ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* ComfortPay `charge()` and `checkTransaction()`: `getTransactionApproval()` returned the transaction status instead of the approval code ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* ComfortPay test mode: `checkCard()` always failed with a PHP warning, `checkTransaction()` read an undefined `cid` ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* CardPay `authorize()` and `cancelAuthorize()` use `testHost` in test mode like the other requests ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* An `ipc` passed to CardPay/ComfortPay `purchase()` or `authorize()` is no longer replaced by `REMOTE_ADDR` ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* Callbacks with missing parameters and malformed cancel-authorization responses (an `Authorization` header part without `=`, a body that is not XML) no longer emit PHP warnings; they are rejected with an exception ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* AES and DES signing threw `ValueError` on PHP 8 (ECB mode has no IV). A failing cipher (e.g. DES without the OpenSSL legacy provider) now throws a `RuntimeException` instead of producing an empty signature ([#24](https://github.com/tomaj/omnipay-tatra/pull/24)).

### Security

* Secrets are redacted from exception traces: the `$secret` of `SignInterface::sign()` (and `HmacSign`, `Aes256Sign`, `DesSign`) and `KeyType::fromSecret()`, and the value of `setSharedSecret()`, `setCertPass()` and `setProxyPassword()` are marked `#[\SensitiveParameter]`. An HMAC or AES key with a non-hexadecimal character throws `SignatureException` instead of reaching `pack()`, whose warning put the key into the trace when an error handler turns warnings into exceptions (previously the signature was computed from a mangled key). Values passed in an `initialize()`/`purchase([...])` array are not covered; the security page recommends `zend.exception_ignore_args=On` ([#49](https://github.com/tomaj/omnipay-tatra/issues/49)).
* **Security**: ComfortPay web service requests verify the bank's TLS certificate and host name; certificate verification was turned off. The system CA store is used by default; pass `caFile` (a CA bundle) or your own `streamContext` when it cannot verify the bank's certificate. The `SoapClient` option `exceptions` was misspelled (`exception`); it had no effect because exceptions are on by default.
* **Security**: **BREAKING CHANGE**: TatraPay `completePurchase()` requires the expected `vs` and rejects a callback whose `VS` differs (`InvalidRequestException('Variable symbol mismatch')`), like CardPay and ComfortPay already did. With AES and DES keys the signature covers only the returned VS and result, so a genuine callback for one order was accepted for another. The response returns the verified VS. Still compare the amount with the order yourself: AES and DES signatures do not cover it ([#33](https://github.com/tomaj/omnipay-tatra/issues/33)).
* **Security**: Signatures received from the bank (`HMAC`, `SIGN`, the cancel-authorization `Authorization` header) are compared with `hash_equals()` instead of `!=`. The loose comparison accepted numeric-looking "magic" signatures (e.g. `SIGN=0` matched the DES signature `0E74203727752110`); a missing or non-string signature is now rejected without PHP warnings ([#24](https://github.com/tomaj/omnipay-tatra/pull/24)).

## [4.3.0] - 2024-10-21

### Added

* ComfortPay: `unRegisterCard()` request (`UnRegisterCardRequest`) that removes a stored card (CID) from ComfortPay.

### Changed

* Tests run on PHP 8.0 - 8.3 in GitHub Actions.

## [4.2.1] - 2023-03-14

### Changed

* CardPay `CompletePurchaseRequest`: the `InvalidRequestException` thrown for an incorrect HMAC signature includes the signed data and the received signature, so a mismatch can be debugged from the log ([#21](https://github.com/tomaj/omnipay-tatra/pull/21)).

## [4.2.0] - 2022-08-23

### Added

* `testHost` gateway parameter: the host of the test-mode endpoint (default `https://platby.tomaj.sk`), so test mode can point to your own simulator ([#20](https://github.com/tomaj/omnipay-tatra/pull/20)).

## [4.1.2] - 2021-04-10

### Added

* CardPay authorization response exposes the masked card number (`CC`) and the approval code (`AC`) ([#19](https://github.com/tomaj/omnipay-tatra/pull/19)).

### Fixed

* CardPay cancel-authorization response check ([#19](https://github.com/tomaj/omnipay-tatra/pull/19)).

## [4.1.1] - 2021-03-17

### Fixed

* ComfortPay `charge()` sent the card transaction twice, so a recurring payment was charged twice. 4.1.0 is affected; upgrade from it immediately.

## [4.1.0] - 2021-03-09

**This version charges recurring ComfortPay payments twice. Do not use it; upgrade to 4.1.1.**

### Added

* CardPay pre-authorization: `authorize()`, `completeAuthorize()` and `cancelAuthorize()` on the CardPay gateway ([#16](https://github.com/tomaj/omnipay-tatra/pull/16)).
* The `RC` (response code) parameter in the CardPay and ComfortPay requests and responses (`getRc()`), which explains why a card payment failed ([#15](https://github.com/tomaj/omnipay-tatra/pull/15)).
* PHPUnit tests for TatraPay, CardPay and ComfortPay, run in GitHub Actions instead of Travis CI ([#14](https://github.com/tomaj/omnipay-tatra/pull/14)).

### Changed

* **BREAKING CHANGE**: ComfortPay `charge()` returns a failed response instead of throwing a `SoapFault` when Tatra banka answers with a `SOAP-ENV:Receiver` fault (the bank's way of declining a card). Code that caught the `SoapFault` to detect a declined charge must check `isSuccessful()` instead ([#17](https://github.com/tomaj/omnipay-tatra/pull/17)).

### Fixed

* Syntax errors in ComfortPay requests.

## [4.0.1] - 2020-08-22

### Fixed

* ComfortPay `charge()` failed with a "class not found" error because it still referenced the response class renamed in 4.0.0 ([#11](https://github.com/tomaj/omnipay-tatra/pull/11)).

## [4.0.0] - 2020-05-06

ComfortPay was rewritten for the new Teleplatba WSDL published by Tatra banka ([#9](https://github.com/tomaj/omnipay-tatra/pull/9)).

### Added

* ComfortPay `listOfExpired()` and `transactionStatus()` requests.

### Changed

* **BREAKING CHANGE**: ComfortPay `ChargeRequest`: the `cid` parameter is renamed to `referedCardId`, and the new `transactionType` parameter is required.
* **BREAKING CHANGE**: ComfortPay `ChargeResponse` is renamed to `CardTransactionResponse`.
* The bundled `comfortpay.wsdl` is replaced by Tatra banka's `Teleplatba_1_0.wsdl` and its XSD files.

## [3.0.0] - 2019-02-20

### Changed

* **BREAKING CHANGE**: Requires `omnipay/common` 3 (was 2). Follow the Omnipay 3 upgrade guide (HTTP client, `Omnipay\Common\Http\ClientInterface`).
* **BREAKING CHANGE**: PHP 5.5 is no longer supported (tested on PHP 5.6 - 7.1).
* `composer.json` declares the required extensions: `openssl`, `hash`, `soap` and `SimpleXML`.

## [0.4.0] - 2017-12-16

### Changed

* ComfortPay SOAP requests go to `comfortpay.tatrabanka.sk` (the load balancer in front of the master and backup servers, as provided by Tatra banka) instead of hardcoded IP addresses ([#7](https://github.com/tomaj/omnipay-tatra/pull/7)).

## [0.3.1] - 2017-05-16

### Fixed

* The `aredir` parameter read a misspelled `aredis` value and was never sent; it is now sent as `0`/`1` ([#5](https://github.com/tomaj/omnipay-tatra/pull/5)).

## [0.3.0] - 2017-04-03

### Changed

* ComfortPay SOAP requests go to the Tatra banka master server instead of the backup server.

## [0.2.1] - 2017-03-27

### Changed

* AES signatures are computed with OpenSSL; the `mcrypt` extension is no longer needed.
* ComfortPay SOAP requests temporarily go to the Tatra banka backup server.

### Fixed

* The `TIMESTAMP` sent to the bank (and signed in the HMAC) is in UTC, as Tatra banka requires, instead of the server's local time.

## [0.2.0] - 2017-02-28

### Added

* ComfortPay SOAP API (client certificate): `checkCard()` (is a stored card still valid), `charge()` (charge a stored card) and `listOfExpirePerId()` (which stored cards expire).

## [0.1.4] - 2017-02-16

### Fixed

* CardPay `completePurchase()` checks that the returned `VS` matches the expected variable symbol and includes the `RC` parameter in the HMAC check; missing optional parameters no longer cause PHP notices.

## [0.1.3] - 2017-02-16

### Fixed

* ComfortPay `completePurchase()` checks that the returned `VS` matches the expected variable symbol; missing optional parameters no longer cause PHP notices.

## [0.1.2] - 2017-02-14

### Fixed

* CardPay `completePurchase()` HMAC check used `RES` instead of `TRES`.

## [0.1.1] - 2017-02-13

### Fixed

* Removed a leftover `var_dump()` that printed output during a request.
* Gateways reference their request classes with `::class` (PSR-4 class names).

## [0.1.0] - 2017-01-09

Initial release. Omnipay 2 gateways for Tatra banka (Slovakia):

* **TatraPay** - payment through Tatra banka internet banking (`purchase()`, `completePurchase()`).
* **CardPay** - card payment (`purchase()`, `completePurchase()`).
* **ComfortPay** - card payment that registers the card for later charges (`purchase()`, `completePurchase()`).
* Signatures with DES (8-character key), AES (64-character hex key) or HMAC-SHA256 (128-character hex key), chosen by the length of the shared secret.
* Test mode sends payments to a test server instead of Tatra banka.

[unreleased]: https://github.com/tomaj/omnipay-tatra/compare/4.3.0...HEAD
[4.3.0]: https://github.com/tomaj/omnipay-tatra/compare/4.2.1...4.3.0
[4.2.1]: https://github.com/tomaj/omnipay-tatra/compare/4.2.0...4.2.1
[4.2.0]: https://github.com/tomaj/omnipay-tatra/compare/4.1.2...4.2.0
[4.1.2]: https://github.com/tomaj/omnipay-tatra/compare/4.1.1...4.1.2
[4.1.1]: https://github.com/tomaj/omnipay-tatra/compare/4.1.0...4.1.1
[4.1.0]: https://github.com/tomaj/omnipay-tatra/compare/4.0.1...4.1.0
[4.0.1]: https://github.com/tomaj/omnipay-tatra/compare/4.0.0...4.0.1
[4.0.0]: https://github.com/tomaj/omnipay-tatra/compare/3.0.0...4.0.0
[3.0.0]: https://github.com/tomaj/omnipay-tatra/compare/0.4.0...3.0.0
[0.4.0]: https://github.com/tomaj/omnipay-tatra/compare/0.3.1...0.4.0
[0.3.1]: https://github.com/tomaj/omnipay-tatra/compare/0.3.0...0.3.1
[0.3.0]: https://github.com/tomaj/omnipay-tatra/compare/0.2.1...0.3.0
[0.2.1]: https://github.com/tomaj/omnipay-tatra/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/tomaj/omnipay-tatra/compare/0.1.4...0.2.0
[0.1.4]: https://github.com/tomaj/omnipay-tatra/compare/0.1.3...0.1.4
[0.1.3]: https://github.com/tomaj/omnipay-tatra/compare/0.1.2...0.1.3
[0.1.2]: https://github.com/tomaj/omnipay-tatra/compare/0.1.1...0.1.2
[0.1.1]: https://github.com/tomaj/omnipay-tatra/compare/0.1.0...0.1.1
[0.1.0]: https://github.com/tomaj/omnipay-tatra/releases/tag/0.1.0
