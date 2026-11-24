---
title: Testing your integration
description: Test a Tatra banka integration without the bank - payment-simulator end to end, unit tests of the return URL with signed callbacks, a mocked HTTP client for cancelAuthorize() and a canned SOAP client for ComfortPay.
---

# Testing your integration

A payment integration has three parts worth testing, each on its own level:

| What | How | Needs |
|---|---|---|
| the redirect and the return, end to end | [test mode](./test-mode) against payment-simulator | a running simulator |
| your return URL handler: paid, failed, forged, foreign | unit tests with a callback you sign yourself | nothing |
| capture/refund and ComfortPay charges | a mocked HTTP client or SOAP client | nothing |

## End to end with payment-simulator

The [examples](/examples/) of this package do exactly this, and CI runs them on every change: a purchase is
redirected to a local simulator, the simulator's signed answers are followed back and verified. The
[shop example](/examples/shop) also runs a real return URL handler on the PHP built-in server.

In your own CI, start the simulator next to your application and point `testHost` at it:

```yaml
- name: Start payment-simulator
  run: |
    git clone --depth 1 https://github.com/tomaj/payment-simulator.git "$RUNNER_TEMP/payment-simulator"
    composer install --working-dir="$RUNNER_TEMP/payment-simulator" --no-dev --no-progress
    php -S 127.0.0.1:8080 -t "$RUNNER_TEMP/payment-simulator/www" "$RUNNER_TEMP/payment-simulator/www/index.php" &
```

[`examples/ci/start-simulator.sh`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/ci/start-simulator.sh)
does the same and waits until the simulator answers. The simulator's pages are plain HTML: open the redirect URL, check
for `Sign OK` and follow the `continue ok`, `continue fail` or `continue timeout` link, like
[`examples/_support/simulator.php`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/_support/simulator.php).

## Unit tests of the return URL

The return is a GET request with a signed query string, so a test can build it: sign the fields with the
library's signer and your test key, put them into a Symfony `Request` and create the gateway with it. Cover at least
the four branches: paid, not paid, an edited result and another order's callback.

<<< @/snippets/testing-callback.php#snippet

Sign with the field order of your key type from the [Signatures](./signatures#signed-fields) page. Never put a
production key into a test; any 128-character hexadecimal string works.

## Capture and refund with a mocked HTTP client

`cancelAuthorize()` sends its request through Omnipay's HTTP client. Pass a client built on `php-http/mock-client` to
the gateway and queue the bank's answer; the request the library sent is available from the mock for assertions.

<<< @/snippets/cardpay-authorize.php#capture

The full setup is in [`docs/snippets/cardpay-authorize.php`](https://github.com/tomaj/omnipay-tatra/blob/master/docs/snippets/cardpay-authorize.php):
`new Gateway(new \Omnipay\Common\Http\Client($mock), $request)`. A signed answer needs an `Authorization: HMAC=...`
header over the XML body; without the header the body is accepted as it is.

## ComfortPay with a canned SOAP client

In test mode the web service requests never reach the bank and answer by the parity of the id (see
[test mode](./test-mode#comfortpay-web-service-in-test-mode)), which is enough to test your branches. To test the real
request and response handling, give the gateway a `SoapClient` that answers with canned XML:

<<< @/snippets/testing-soap.php#snippet

A decline is a SOAP fault with `SOAP-ENV:Receiver` and an `errorCode` in the detail; the package's own tests build
them in [`tests/Support/FakeSoapClient.php`](https://github.com/tomaj/omnipay-tatra/blob/master/tests/Support/FakeSoapClient.php).

## Before you go live

- Switch `testMode` off and use the production `mid` and key from Tatra banka; keep them out of the repository.
- Make one real payment with a small amount for each gateway and key type you use, and refund it.
- Check that a reload of the return URL does not ship the order twice, and that a rejected callback changes nothing.
