# Examples

Runnable recipes for omnipay-tatra. Each one is documented on the site: https://tomaj.github.io/omnipay-tatra/examples/

| Directory | Shows |
|---|---|
| `tatrapay/` | TatraPay purchase and every kind of return, a forged and a foreign callback |
| `cardpay/` | CardPay with an HMAC and an AES key, customer name validation |
| `cardpay-preauthorization/` | pre-authorization, capture, release and refund |
| `comfortpay-recurring/` | card registration and recurring charges |
| `shop/` | a shop with a return URL handler on the PHP built-in server |
| `signatures/` | HMAC, AES and DES signatures checked with the openssl CLI |

```bash
examples/ci/example.sh tatrapay   # composer install, start payment-simulator, run e2e.sh
```

Every example's `e2e.sh` compares its output with `expected-output.txt`; the Examples workflow runs all of them
against the code of each pull request. `_support/simulator.php` plays the customer at payment-simulator.
