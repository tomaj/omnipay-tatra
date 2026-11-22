# Upgrading from 4.x to 5.0

5.0 requires PHP 8.4, adds native types and makes the classes `final`. Most applications only need to check the
points marked with **(action)**.

## PHP 8.4 or newer

`composer.json` requires `"php": "^8.4"`. Stay on 4.x until you run PHP 8.4 ([#28](https://github.com/tomaj/omnipay-tatra/issues/28)).

## TatraPay `completePurchase()` requires `vs`

**(action)** Pass the variable symbol of the order you expect. A callback for a different VS is rejected
([#33](https://github.com/tomaj/omnipay-tatra/issues/33)):

```php
// 4.x: any correctly signed callback was successful; you had to compare getVs() yourself
$response = $gateway->completePurchase(['amount' => '10.00', 'currency' => 'EUR'])->send();
if ($response->isSuccessful() && $response->getVs() === $order->vs) { /* paid */ }

// 5.0
$response = $gateway->completePurchase(['vs' => $order->vs, 'amount' => '10.00', 'currency' => 'EUR'])->send();
if ($response->isSuccessful()) { /* paid */ } // InvalidRequestException('Variable symbol mismatch') otherwise
```

With AES and DES keys the bank signs only the VS and the result, not the amount: compare the amount of the order
yourself before you ship it.

## ComfortPay verifies the bank's TLS certificate

The ComfortPay web service client verifies the certificate and host name of `comfortpay.tatrabanka.sk` against the
system CA store. **(action)** If your server's CA store cannot verify it, pass a CA bundle instead of turning the
verification off:

```php
$gateway->setCaFile('/etc/ssl/certs/ca-certificates.crt');
// or full control over the TLS options:
$gateway->setStreamContext(stream_context_create(['ssl' => ['cafile' => '/path/to/ca.pem', 'verify_peer' => true]]));
```

## Native types on every accessor

Parameter setters take `?string` (or the type named below) and getters return `?string`. Omnipay's `initialize()` and
the request factories (`$gateway->purchase([...])`) still accept integers because they call the setters from
non-strict code, and the getters return the value as a string. **(action)** Only a direct setter call from a file with
`declare(strict_types=1)` needs a string:

```php
// 4.x
$gateway->setMid(1111);

// 5.0
$gateway->setMid('1111');
```

Other signature changes:

| 4.x | 5.0 |
|---|---|
| `SignInterface::sign($input, $secret)` | `sign(string $input, string $secret): string` |
| `getAredir()` returned `0`, `1` or `null` | same, typed `?int`; `setAredir(bool\|int\|string\|null)` |
| `ListOfExpirePerIdRequest::setCardIds($value)` | `setCardIds(?array $value)`; `getCardIds()` returns `list<string>` |
| `$request->send()` returned `ResponseInterface` | returns the concrete response class, e.g. `TatraPay\Message\CompletePurchaseResponse` |

## Classes are final

Gateways, requests, responses and signers are `final` ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
**(action)** If you extended one of them, wrap it instead or implement Omnipay's interfaces. `Core\Message\AbstractRequest`
and `ComfortPay\Message\AbstractSoapRequest` stay abstract base classes of the package.

## Callbacks read the Omnipay HTTP request, not `$_GET`

`completePurchase()` and `completeAuthorize()` read the bank's query parameters from the request the gateway was created
with. `Omnipay::create()` and `new Gateway()` without arguments use `Request::createFromGlobals()`, so nothing changes
for them. **(action)** If you pass your own Symfony request, it must carry the query string:

```php
// 4.x: the gateway ignored $httpRequest and read $_GET
$gateway = new \Omnipay\TatraPay\Gateway($httpClient, $httpRequest);

// 5.0: $httpRequest->query must contain VS, RES, SIGN/HMAC, ...
$gateway = new \Omnipay\TatraPay\Gateway($httpClient, \Symfony\Component\HttpFoundation\Request::createFromGlobals());
```

Missing parameters no longer emit "Undefined array key" warnings; the callback is rejected.

## Strict comparisons

The variable symbol returned by the bank must equal the expected one exactly: `0123` no longer matches `123`
(CardPay and ComfortPay `completePurchase()`, CardPay `completeAuthorize()`). Pass `vs` the same way you passed it to
`purchase()`.

## Exceptions

Every exception the package throws implements `Omnipay\Core\Exception\TatraException`, except Omnipay's own
`InvalidRequestException` (invalid parameters, wrong callback signature or VS) and `InvalidResponseException` (wrong
signature or invalid XML from the bank). The new classes extend the SPL exceptions thrown before, so existing `catch`
blocks keep working:

| Situation | 4.x | 5.0 |
|---|---|---|
| Shared secret with an unsupported length | `\Exception('Unknown key length')` | `UnsupportedKeyException` (`\InvalidArgumentException`) |
| Cipher missing in OpenSSL (DES without the legacy provider) | `\RuntimeException` | `SignatureException` (`\RuntimeException`) |
| `cancelAuthorize()`: HTTP status other than 200 | `\Exception` | `GatewayException` (`\RuntimeException`) |
| `cancelAuthorize()`: response is not XML | `\Exception` from `SimpleXMLElement` | `InvalidResponseException` |
| Unsupported `txn` | `\UnexpectedValueException` | `InvalidParameterException` (`\UnexpectedValueException`) |
| Unsupported ComfortPay `transactionType` | sent to the bank | `InvalidParameterException` |

```php
try {
    $response = $gateway->completePurchase(['vs' => $order->vs, 'amount' => $order->amount, 'currency' => 'EUR'])->send();
} catch (\Omnipay\Common\Exception\InvalidRequestException $e) {
    // forged or broken callback
} catch (\Omnipay\Core\Exception\TatraException $e) {
    // configuration or communication problem
}
```

## Enums instead of constant sets

The constants still exist but are deprecated:

| 4.x | 5.0 |
|---|---|
| `CancelAuthorizeRequest::TXN_CLOSE_PREAUTHORIZATION` / `TXN_STORNO_PREAUTHORIZATION` / `TXN_CHARGEBACK` | `CancelAuthorizeTxn::ClosePreauthorization` / `StornoPreauthorization` / `Chargeback` |
| `ComfortPay\Gateway::TRANSACTION_TYPE_*` | `ComfortPay\TransactionType::Purchase`, `Preauthorization`, `PreauthConfirm`, `PreauthCancel`, `Chargeback` |
| `ComfortPay\Gateway::CARD_STATUS_*` | `ComfortPay\CardStatus::Ok`, `Fail`, `Unknown` (`CheckCardResponse::getCardStatus()`) |

```php
// 4.x
$gateway->cancelAuthorize(['txn' => \Omnipay\CardPay\Message\CancelAuthorizeRequest::TXN_CHARGEBACK, ...]);

// 5.0 (the string 'CB' still works too)
$gateway->cancelAuthorize(['txn' => \Omnipay\CardPay\Message\CancelAuthorizeTxn::Chargeback, ...]);
```

## Behaviour fixes you may notice

- CardPay `authorize()`: `tdsCardholder` used to overwrite the `E2E` parameter and was sent as both `E2E` and
  `TDS_CARDHOLDER`; `TDS_SHIP_CITY` was signed but not sent. Both are sent as their own fields now.
- ComfortPay `charge()` / `checkTransaction()`: `getTransactionApproval()` returned the transaction status; it returns
  the approval code now.
- An `ipc` you pass explicitly is no longer replaced by `REMOTE_ADDR`.
