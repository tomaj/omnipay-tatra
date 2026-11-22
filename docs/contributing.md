---
title: Contributing
description: How to contribute to omnipay-tatra - checks, tests with openssl-verified signatures, and this documentation.
---

# Contributing

Pull requests are welcome on [tomaj/omnipay-tatra](https://github.com/tomaj/omnipay-tatra). The project policy is
[CONTRIBUTING.md](https://github.com/tomaj/omnipay-tatra/blob/master/CONTRIBUTING.md); participation follows the
[code of conduct](https://github.com/tomaj/omnipay-tatra/blob/master/CODE_OF_CONDUCT.md). Report vulnerabilities in
private, see [SECURITY.md](https://github.com/tomaj/omnipay-tatra/blob/master/SECURITY.md).

## Checks

```bash
composer install
composer check      # code style, PHPStan (level max), Rector, PHPUnit
composer coverage   # HTML and Clover report in build/ (Xdebug or PCOV)
composer infection  # mutation testing
```

CI runs the tests on PHP 8.4 (also with the lowest dependencies), 8.5 and 8.6, requires 95 % statement coverage and
runs Infection; a nightly PHP 8.7 job may fail.

## Signatures in tests

Expected signatures are computed independently with the `openssl` CLI, never with the library's own signers; the
commands are on the [Signatures](/guide/signatures) page. DES needs OpenSSL's legacy provider; without it the DES tests
are skipped locally, CI runs them with the provider enabled.

## This documentation

The site is VitePress in `docs/`. Code examples live in `docs/snippets/*.php` and are imported into the pages, and
`tests/DocsSnippetsTest.php` runs every one of them, so a documented example cannot drift from the code.

```bash
cd docs
npm ci
npm run docs:dev    # local preview
npm run docs:links  # build and check every link, as CI does
```
