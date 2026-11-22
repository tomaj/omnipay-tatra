# Omnipay: Tatra banka

**Tatra banka (TatraPay, CardPay, ComfortPay) gateways for the Omnipay payment processing library**

**Documentation: https://tomaj.github.io/omnipay-tatra/**

[Getting started](https://tomaj.github.io/omnipay-tatra/guide/getting-started) · [API reference](https://tomaj.github.io/omnipay-tatra/api/reference) · [Changelog](https://tomaj.github.io/omnipay-tatra/changelog)

[![Latest Stable Version](https://img.shields.io/packagist/v/tomaj/omnipay-tatra.svg)](https://packagist.org/packages/tomaj/omnipay-tatra)
[![CI](https://github.com/tomaj/omnipay-tatra/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/tomaj/omnipay-tatra/actions/workflows/ci.yml?query=branch%3Amaster)
[![Coverage Status](https://coveralls.io/repos/github/tomaj/omnipay-tatra/badge.svg?branch=master)](https://coveralls.io/github/tomaj/omnipay-tatra?branch=master)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen.svg)](https://phpstan.org/)
[![OpenSSF Scorecard](https://api.securityscorecards.dev/projects/github.com/tomaj/omnipay-tatra/badge)](https://scorecard.dev/viewer/?uri=github.com/tomaj/omnipay-tatra)
[![Documentation](https://img.shields.io/badge/docs-5.x-4f46e5.svg)](https://tomaj.github.io/omnipay-tatra/)
[![License](https://img.shields.io/packagist/l/tomaj/omnipay-tatra.svg)](LICENSE.md)
[![PHP](https://img.shields.io/packagist/dependency-v/tomaj/omnipay-tatra/php.svg)](https://packagist.org/packages/tomaj/omnipay-tatra)

## What is omnipay-tatra?

[Omnipay](https://omnipay.thephpleague.com/) gateways for the online payment services of [Tatra banka](https://www.tatrabanka.sk/) (Slovakia):

- **TatraPay**: the customer pays from their Tatra banka internet banking.
- **CardPay**: card payments, including pre-authorization, capture, reversal and refund.
- **ComfortPay**: CardPay that registers the card, plus the SOAP API to charge a registered card later (recurring payments) and to manage registered cards.

Requests are signed with HMAC-SHA256 (128-character key), AES (64-character key) or DES (8-character key); the callback from the bank is verified with the same key.

## Installation

Requires PHP 8.4 or newer with the `openssl`, `soap` and `simplexml` extensions, and a PSR-18 HTTP client for Omnipay (for example `guzzlehttp/guzzle`).

```bash
composer require tomaj/omnipay-tatra
```

## Usage

Redirect the customer to the bank:

```php
use Omnipay\Omnipay;

$gateway = Omnipay::create('TatraPay');
$gateway->setMid('9999');
$gateway->setSharedSecret($hmacKey); // 128 hexadecimal characters

$response = $gateway->purchase([
    'amount' => '10.00',
    'currency' => 'EUR',
    'vs' => '123456',
    'rurl' => 'https://shop.example/payment/return',
])->send();

$response->redirect();
```

Verify the customer's return to `rurl`:

```php
$response = $gateway->completePurchase([
    'amount' => '10.00',
    'currency' => 'EUR',
    'vs' => '123456', // the variable symbol of the order you expect
])->send();

if ($response->isSuccessful()) {
    // paid: mark order 123456 as paid
}
```

`completePurchase()` throws `Omnipay\Common\Exception\InvalidRequestException` when the signature is wrong or the bank returned a different variable symbol. See the [documentation](https://tomaj.github.io/omnipay-tatra/) for CardPay, ComfortPay, test mode and the signatures.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) and the [Code of Conduct](CODE_OF_CONDUCT.md).

## Security

Report vulnerabilities in private, see [SECURITY.md](SECURITY.md). Do not open a public issue.

## License

MIT, see [LICENSE.md](LICENSE.md).
