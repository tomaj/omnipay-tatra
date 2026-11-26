---
title: Prechod na 5.0
description: Všetky nekompatibilné zmeny medzi omnipay-tatra 4.x a 5.0 s kódom pred a po zmene.
---

# Prechod zo 4.x na 5.0

Verzia 5.0 vyžaduje PHP 8.4, pridáva natívne typy a triedy sú `final`. Väčšine aplikácií stačí skontrolovať body
označené **(úprava)**. Anglický originál je [UPGRADE-5.0.md](https://github.com/tomaj/omnipay-tatra/blob/master/UPGRADE-5.0.md).

## PHP 8.4 alebo novšie {#php-8-4-or-newer}

`composer.json` vyžaduje `"php": "^8.4"`. Kým nemáte PHP 8.4, zostaňte na 4.x ([#28](https://github.com/tomaj/omnipay-tatra/issues/28)).

## TatraPay `completePurchase()` vyžaduje `vs` {#tatrapay-completepurchase-requires-vs}

**(úprava)** Odovzdajte variabilný symbol objednávky, ktorú očakávate. Návrat z banky s iným VS sa odmietne
([#33](https://github.com/tomaj/omnipay-tatra/issues/33)):

<!-- snippet: skip -->
```php
// 4.x: any correctly signed callback was successful; you had to compare getVs() yourself
$response = $gateway->completePurchase(['amount' => '10.00', 'currency' => 'EUR'])->send();
if ($response->isSuccessful() && $response->getVs() === $order->vs) { /* paid */ }

// 5.0
$response = $gateway->completePurchase(['vs' => $order->vs, 'amount' => '10.00', 'currency' => 'EUR'])->send();
if ($response->isSuccessful()) { /* paid */ } // InvalidRequestException('Variable symbol mismatch') otherwise
```

Pri AES a DES kľúčoch banka podpisuje len VS a výsledok, nie sumu: pred odoslaním tovaru porovnajte sumu objednávky
sami.

## ComfortPay overuje TLS certifikát banky {#comfortpay-verifies-the-bank-s-tls-certificate}

Klient webovej služby ComfortPay overuje certifikát a názov hostiteľa `comfortpay.tatrabanka.sk` voči systémovému
úložisku certifikačných autorít (CA). **(úprava)** Ak ho CA úložisko vášho servera overiť nevie, odovzdajte CA bundle
namiesto vypnutia overovania:

<!-- snippet: skip -->
```php
$gateway->setCaFile('/etc/ssl/certs/ca-certificates.crt');
// or full control over the TLS options:
$gateway->setStreamContext(stream_context_create(['ssl' => ['cafile' => '/path/to/ca.pem', 'verify_peer' => true]]));
```

## Natívne typy na každom getteri a setteri {#native-types-on-every-accessor}

Settery parametrov berú `?string` (alebo typ uvedený nižšie) a gettery vracajú `?string`. `initialize()` z Omnipay a
továrenské metódy requestov (`$gateway->purchase([...])`) stále prijímajú celé čísla, pretože settery volajú z kódu
bez strict typov, a gettery vrátia hodnotu ako reťazec. **(úprava)** Reťazec potrebuje len priame volanie settera
zo súboru s `declare(strict_types=1)`:

<!-- snippet: skip -->
```php
// 4.x
$gateway->setMid(1111);

// 5.0
$gateway->setMid('1111');
```

Ďalšie zmeny signatúr:

| 4.x | 5.0 |
|---|---|
| `SignInterface::sign($input, $secret)` | `sign(string $input, string $secret): string` |
| `getAredir()` vracal `0`, `1` alebo `null` | rovnako, s typom `?int`; `setAredir(bool\|int\|string\|null)` |
| `ListOfExpirePerIdRequest::setCardIds($value)` | `setCardIds(?array $value)`; `getCardIds()` vracia `list<string>` |
| `$request->send()` vracal `ResponseInterface` | vracia konkrétnu triedu odpovede, napr. `TatraPay\Message\CompletePurchaseResponse` |

## Triedy sú finálne {#classes-are-final}

Brány, requesty, odpovede aj podpisovače sú `final` ([#29](https://github.com/tomaj/omnipay-tatra/issues/29)).
**(úprava)** Ak ste niektorú z nich rozširovali, obaľte ju alebo implementujte rozhrania Omnipay.
`Core\Message\AbstractRequest` a `ComfortPay\Message\AbstractSoapRequest` zostávajú abstraktnými základnými triedami
balíka.

## Návraty z banky čítajú HTTP požiadavku Omnipay, nie `$_GET` {#callbacks-read-the-omnipay-http-request-not-get}

`completePurchase()` a `completeAuthorize()` čítajú query parametre od banky z požiadavky, s ktorou bola brána
vytvorená. `Omnipay::create()` a `new Gateway()` bez argumentov používajú `Request::createFromGlobals()`, takže pre ne
sa nič nemení. **(úprava)** Ak odovzdávate vlastnú Symfony požiadavku, musí obsahovať query string:

<!-- snippet: skip -->
```php
// 4.x: the gateway ignored $httpRequest and read $_GET
$gateway = new \Omnipay\TatraPay\Gateway($httpClient, $httpRequest);

// 5.0: $httpRequest->query must contain VS, RES, SIGN/HMAC, ...
$gateway = new \Omnipay\TatraPay\Gateway($httpClient, \Symfony\Component\HttpFoundation\Request::createFromGlobals());
```

Chýbajúce parametre už nevyvolávajú varovania „Undefined array key“; návrat z banky sa odmietne.

## Striktné porovnávanie {#strict-comparisons}

Variabilný symbol vrátený bankou sa musí presne zhodovať s očakávaným: `0123` sa už nezhoduje so `123`
(CardPay a ComfortPay `completePurchase()`, CardPay `completeAuthorize()`). Odovzdajte `vs` rovnako, ako ste ho
odovzdali do `purchase()`.

## Výnimky {#exceptions}

Každá výnimka, ktorú balík vyhodí, implementuje `Omnipay\Core\Exception\TatraException`, okrem vlastných výnimiek
Omnipay `InvalidRequestException` (neplatné parametre, nesprávny podpis návratu z banky alebo VS) a
`InvalidResponseException` (nesprávny podpis alebo neplatné XML od banky). Nové triedy rozširujú SPL výnimky, ktoré sa
vyhadzovali predtým, takže existujúce bloky `catch` fungujú ďalej:

| Situácia | 4.x | 5.0 |
|---|---|---|
| Kľúč s nepodporovanou dĺžkou | `\Exception('Unknown key length')` | `UnsupportedKeyException` (`\InvalidArgumentException`) |
| V OpenSSL chýba šifra (DES bez legacy providera) | `\RuntimeException` | `SignatureException` (`\RuntimeException`) |
| `cancelAuthorize()`: iný HTTP status ako 200 | `\Exception` | `GatewayException` (`\RuntimeException`) |
| `cancelAuthorize()`: odpoveď nie je XML | `\Exception` zo `SimpleXMLElement` | `InvalidResponseException` |
| Nepodporovaný `txn` | `\UnexpectedValueException` | `InvalidParameterException` (`\UnexpectedValueException`) |
| Nepodporovaný ComfortPay `transactionType` | odoslal sa do banky | `InvalidParameterException` |

```php
try {
    $response = $gateway->completePurchase(['vs' => $order->vs, 'amount' => $order->amount, 'currency' => 'EUR'])->send();
} catch (\Omnipay\Common\Exception\InvalidRequestException $e) {
    // forged or broken callback
} catch (\Omnipay\Core\Exception\TatraException $e) {
    // configuration or communication problem
}
```

## Enumy namiesto skupín konštánt {#enums-instead-of-constant-sets}

Konštanty stále existujú s hodnotami zo 4.x, sú však zastarané (deprecated) a verzia 6.0 ich odstráni. Majú atribút
PHP `#[\Deprecated]`, takže každé ich čítanie vyvolá `E_USER_DEPRECATED` s názvom náhrady, napríklad
`Constant Omnipay\CardPay\Message\CancelAuthorizeRequest::TXN_CHARGEBACK is deprecated since 5.0, use Omnipay\CardPay\Message\CancelAuthorizeTxn::Chargeback`.
Testovacia sada, ktorá zlyhá pri deprecation (`failOnDeprecation` v PHPUnit), zlyhá, kým neprejdete na enumy.
**(úprava)** Použite enumy:

| 4.x | 5.0 |
|---|---|
| `CancelAuthorizeRequest::TXN_CLOSE_PREAUTHORIZATION` / `TXN_STORNO_PREAUTHORIZATION` / `TXN_CHARGEBACK` | `CancelAuthorizeTxn::ClosePreauthorization` / `StornoPreauthorization` / `Chargeback` |
| `ComfortPay\Gateway::TRANSACTION_TYPE_*` | `ComfortPay\TransactionType::Purchase`, `Preauthorization`, `PreauthConfirm`, `PreauthCancel`, `Chargeback` |
| `ComfortPay\Gateway::CARD_STATUS_*` | `ComfortPay\CardStatus::Ok`, `Fail`, `Unknown` (`CheckCardResponse::getCardStatus()`) |

<!-- snippet: skip -->
```php
// 4.x
$gateway->cancelAuthorize(['txn' => \Omnipay\CardPay\Message\CancelAuthorizeRequest::TXN_CHARGEBACK, ...]);

// 5.0 (the string 'CB' still works too)
$gateway->cancelAuthorize(['txn' => \Omnipay\CardPay\Message\CancelAuthorizeTxn::Chargeback, ...]);
```

## Meno zákazníka sa kontroluje {#customer-name-is-validated}

CardPay `purchase()`/`authorize()` a ComfortPay `purchase()` vyhodia `InvalidRequestException` pre `name` so znakmi,
ktoré Tatra banka neprijíma ([#3](https://github.com/tomaj/omnipay-tatra/issues/3)). **(úprava)** Najprv odstráňte
diakritiku:

<!-- snippet: skip -->
```php
// 4.x: the bank showed an error page
$gateway->purchase(['name' => 'Ján Novák', ...]);

// 5.0
$name = preg_replace('/[^0-9a-zA-Z .\-_@]/', '', (string) iconv('UTF-8', 'ASCII//TRANSLIT', 'Ján Novák'));
$gateway->purchase(['name' => $name, ...]);
```

## Opravy správania, ktoré si môžete všimnúť {#behaviour-fixes-you-may-notice}

- CardPay `authorize()`: `tdsCardholder` predtým prepisoval parameter `E2E` a posielal sa ako `E2E` aj
  `TDS_CARDHOLDER`; `TDS_SHIP_CITY` sa podpisoval, ale neposielal. Teraz sa obe posielajú ako samostatné polia.
- ComfortPay `charge()` / `checkTransaction()`: `getTransactionApproval()` vracal stav transakcie; teraz vracia
  autorizačný kód.
- `ipc`, ktoré odovzdáte explicitne, sa už nenahrádza hodnotou `REMOTE_ADDR`.
