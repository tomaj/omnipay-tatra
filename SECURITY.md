# Security Policy

## Supported Versions

Security fixes are provided for the lines below.

| Version | Supported |
| ------- | --------- |
| 5.x     | Yes, once released: bug fixes and security fixes |
| 4.x     | Yes: bug fixes and security fixes until 5.0.0 is released, then security fixes only for six months |
| < 4.0   | No |

4.x is the current stable line; `master` holds 5.0.0. After the 5.0.0 release, 4.x gets fixes for security vulnerabilities only, for six months; there is no 4.x maintenance branch, so a fix is released from one created from the `4.3.0` tag.

## Reporting a Vulnerability

Do not open a public GitHub issue for a security vulnerability.

The preferred way to report a vulnerability is [GitHub private vulnerability reporting](https://github.com/tomaj/omnipay-tatra/security/advisories/new).

If you cannot use private vulnerability reporting, email the details to **tomasmajer@gmail.com**.

Please include:

- A description of the vulnerability
- Steps to reproduce, or a proof of concept
- The omnipay-tatra version and the gateway involved (TatraPay, CardPay, ComfortPay)
- The potential impact
- A suggested fix, if you have one

### Response

You will receive an acknowledgement within 14 days.

### Coordinated disclosure

We follow coordinated disclosure. Please give us time to prepare a fix before discussing the issue in public.

When a fix is ready, we publish a [GitHub Security Advisory](https://github.com/tomaj/omnipay-tatra/security/advisories) and record the security fix in the [CHANGELOG](CHANGELOG.md).

## Security considerations

This library signs payment requests and verifies the bank's callbacks. To use it safely:

- Keep the `sharedSecret` and the ComfortPay client certificate (`certPath`) and its passphrase (`certPass`) out of the repository; load them from the environment or a secret store.
- Always confirm a payment with `completePurchase()` / `completeAuthorize()` on the return URL, pass the variable symbol (`vs`), amount and currency of the order you expect, and only then mark the order as paid. AES and DES signatures do not cover the amount: compare it with the order yourself.
- Never use test mode (`testMode`, `testHost`) in production: it sends payments to a simulator.
- The ComfortPay SOAP client verifies the bank's TLS certificate. Do not turn the verification off; pass your own CA file only if the system CA store cannot be used.
- Prefer HMAC keys: Tatra banka issues them to new merchants, and DES is a legacy cipher.
