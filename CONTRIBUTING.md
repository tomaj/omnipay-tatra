# Contributing

Contributions are welcome and will be credited.

This project follows the [Contributor Covenant Code of Conduct](CODE_OF_CONDUCT.md). By participating, you agree to uphold it.

Security vulnerabilities are reported in private. See [SECURITY.md](SECURITY.md). Do not open a public issue for a security report.

## Pull requests

- Follow [PER Coding Style](https://www.php-fig.org/per/coding-style/). `composer phpcs` checks `src` and `tests`.
- Add tests for every behaviour change. A patch without tests will not be accepted.
- Signatures: expected values in tests are computed independently with the `openssl` CLI, never with the library's own signers.
- Document behaviour changes in the documentation (`docs/`) and `CHANGELOG.md`.
- Follow [Semantic Versioning](https://semver.org/). The current stable line is 4.x; `master` holds 5.0. Breaking changes wait for the next major version.
- Open one pull request per change, from a feature branch. Do not ask us to pull from your `master` branch. `master` is protected: every change lands through a pull request once the required CI checks (PHP 8.4, 8.4 `--prefer-lowest`, 8.5, 8.6, code style, PHPStan, Rector, security audit, Infection) are green.
- Keep the history coherent. Squash work-in-progress commits before submitting.

## Development

omnipay-tatra supports PHP 8.4, 8.5 and 8.6. Continuous integration runs on PHP 8.4, PHP 8.4 with the lowest dependencies, PHP 8.5, and PHP 8.6, which also runs the 90% coverage check and Infection. A nightly PHP 8.7 build runs too, but its failure does not fail the workflow.

```bash
git clone https://github.com/tomaj/omnipay-tatra.git
cd omnipay-tatra
composer install
composer phpunit
composer phpstan
composer phpcs
composer rector
```

PHPUnit fails on any notice, deprecation or warning, including PHPUnit's own. Deprecations that one dependency raises inside another are ignored, so the lowest supported dependencies still pass.

DES signatures need the `des-ecb` cipher, which OpenSSL 3 only offers through its legacy provider. Without it the DES tests are skipped; CI runs them a second time on PHP 8.6 with the legacy provider enabled.

`composer phpunit` sets `XDEBUG_MODE=off` and passes `--no-coverage`. To generate an HTML and Clover coverage report, or to run Infection (requires Xdebug or PCOV):

```bash
composer coverage
composer infection
```

The documentation site lives in `docs/` (VitePress):

```bash
cd docs
npm ci
npm run docs:dev
```

Maintainers publish releases by pushing a version tag; see [RELEASING.md](RELEASING.md).

By contributing, you agree that your contributions are licensed under the MIT License.
