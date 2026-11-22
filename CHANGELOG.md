# Change Log
All notable changes to this project will be documented in this file.
Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [Unreleased][unreleased]

### Changed

* **BREAKING CHANGE**: Requires PHP 8.4 or newer (`"php": "^8.4"`; there was no PHP constraint before and CI covered PHP 8.0 - 8.3). Stay on 4.x for older PHP ([#28](https://github.com/tomaj/omnipay-tatra/issues/28)).
* CI runs in one GitHub Actions workflow: tests on PHP 8.3 - 8.6 (plus `--prefer-lowest`) and a nightly PHP 8.7 job that may fail, PHP-CS-Fixer (PER-CS, replaces PHP_CodeSniffer), PHPStan level 5 with strict rules, and `composer audit`. Dependabot keeps Composer packages and actions up to date ([#24](https://github.com/tomaj/omnipay-tatra/pull/24)).
* **BREAKING CHANGE**: Native parameter and return types on every accessor and message method: setters take `?string` (pass strings when you call them directly from strict-types code; `initialize()` and the request factories still accept integers), getters return `?string`, `SignInterface::sign(string $input, string $secret): string`, and `send()` returns the concrete response class. See [UPGRADE-5.0.md](UPGRADE-5.0.md) ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* **BREAKING CHANGE**: Gateways, requests, responses and signers are `final`; extend through Omnipay's interfaces instead ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* **BREAKING CHANGE**: `completePurchase()` and `completeAuthorize()` read the bank's parameters from the gateway's HTTP request (`Request::createFromGlobals()` by default) instead of `$_GET`; a custom request passed to the gateway must carry the query string ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* **BREAKING CHANGE**: Variable symbols returned by the bank are compared strictly (`0123` no longer matches `123`) in CardPay and ComfortPay `completePurchase()` and CardPay `completeAuthorize()` ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* **BREAKING CHANGE**: Every exception the package throws implements `Omnipay\Core\Exception\TatraException` (besides Omnipay's `InvalidRequestException`/`InvalidResponseException`): `UnsupportedKeyException` replaces `\Exception('Unknown key length')`, `SignatureException` the `\RuntimeException` of the signers, `GatewayException` the `\Exception` of a failed cancel-authorization request, `InvalidParameterException` the `\UnexpectedValueException` of an unsupported TXN. ComfortPay `charge()` rejects an unknown `transactionType` before calling the bank ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* Enums `CardPay\Message\CancelAuthorizeTxn`, `ComfortPay\TransactionType`, `ComfortPay\CardStatus` and `Core\Sign\KeyType` replace the constant sets and the key-length checks; the constants are deprecated ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* PHPStan runs at level max with bleedingEdge, strict, deprecation and PHPUnit rules; PHP-CS-Fixer enforces `declare(strict_types=1)` and the PHP 8.4 migration rules; Rector checks the code; new composer scripts `rector`, `rector-fix`, `coverage` and `infection` ([#27](https://github.com/tomaj/omnipay-tatra/issues/27)).
* The Composer dist archive contains only the library (`src/`, `composer.json`, README, LICENSE, CHANGELOG); the ad-hoc scripts `index.php`, `test.php` and `testserver.php` were removed. Added `LICENSE.md` (MIT), `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, `SECURITY.md` and `RELEASING.md`; `omnipay/common` is required as `^3.2` ([#25](https://github.com/tomaj/omnipay-tatra/issues/25)).

### Fixed

* CardPay `authorize()`: `tdsCardholder` overwrote the `E2E` parameter, and `TDS_SHIP_CITY` was signed but not sent, so the bank rejected the signature when it was set ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* ComfortPay `charge()` and `checkTransaction()`: `getTransactionApproval()` returned the transaction status instead of the approval code ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* ComfortPay test mode: `checkCard()` always failed with a PHP warning, `checkTransaction()` read an undefined `cid` ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* CardPay `authorize()` and `cancelAuthorize()` use `testHost` in test mode like the other requests ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* An `ipc` passed to CardPay/ComfortPay `purchase()` or `authorize()` is no longer replaced by `REMOTE_ADDR` ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* Callbacks with missing parameters and malformed cancel-authorization responses (an `Authorization` header part without `=`, a body that is not XML) no longer emit PHP warnings; they are rejected with an exception ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
* AES and DES signing threw `ValueError` on PHP 8 (ECB mode has no IV). A failing cipher (e.g. DES without the OpenSSL legacy provider) now throws a `RuntimeException` instead of producing an empty signature ([#24](https://github.com/tomaj/omnipay-tatra/pull/24)).

### Security

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
