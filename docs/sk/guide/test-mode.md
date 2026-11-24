---
title: Testovací režim
description: Posielajte požiadavky TatraPay, CardPay a ComfortPay do simulátora platobnej brány namiesto Tatra banky.
---

# Testovací režim

S `testMode` idú požiadavky s presmerovaním do simulátora a požiadavky na webovú službu ComfortPay sa vybavia lokálne
bez volania banky. V produkcii ho nikdy nezapínajte.

```php
$gateway->setTestMode(true);
$gateway->setTestHost('http://localhost:4444'); // optional, default https://platby.tomaj.sk
```

## Adresy {#endpoints}

| Požiadavka | Produkcia | Testovací režim |
|---|---|---|
| TatraPay HMAC | `https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/tatrapay` | `{testHost}/payment/tatrapay-hmac` |
| TatraPay AES / DES | `https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp` | `{testHost}/payment/tatrapay-aes256` / `tatrapay-des` |
| CardPay HMAC | `https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/cardpay` | `{testHost}/payment/cardpay-hmac` |
| CardPay AES / DES | `https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp` | `{testHost}/payment/cardpay-aes256` / `cardpay-des` |
| CardPay `authorize()` | `https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/cardpay` | `{testHost}/payment/cardpay-authorize-hmac` |
| CardPay `cancelAuthorize()` | `https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/txn_process.jsp` | `{testHost}/payment/cardpay-cancel-purchase` |
| ComfortPay HMAC / AES | ako CardPay | `{testHost}/payment/comfortpay-hmac` / `comfortpay-aes256` |
| Webová služba ComfortPay | `https://comfortpay.tatrabanka.sk:8011/services/Teleplatba_1_0` | žiadna požiadavka, pozrite nižšie |

Na `https://platby.tomaj.sk` beží [payment-simulator](https://github.com/tomaj/payment-simulator); spustite si ho sami
a nasmerujte naň `testHost`, aby ste celý priebeh platby otestovali lokálne.

## payment-simulator {#payment-simulator}

Simulátor platobnej brány prijíma tie isté podpísané požiadavky ako banka, podpísané jeho verejnými testovacími
kľúčmi, a zobrazí stránku s verdiktom (**Sign OK**, **Wrong sign** alebo **Missing parameters**) a tromi odkazmi späť
na vašu `rurl`: úspešnú (`RES=OK`), neúspešnú (`FAIL`) a platbu s timeoutom (`TOUT`), podpísané tak, ako ich podpisuje
banka.

| Typ kľúča | Testovací kľúč |
|---|---|
| HMAC | `str_repeat('1', 128)` |
| AES | `str_repeat('1', 64)` |
| DES | `'11111111'` |

Funguje akékoľvek `mid`. Spustite ho lokálne, bez Dockera:

```bash
examples/ci/start-simulator.sh   # in a checkout of omnipay-tatra: http://127.0.0.1:8080
```

Skript naklonuje payment-simulator do dočasného adresára (alebo použije `PAYMENT_SIMULATOR_DIR`), nainštaluje ho
a spustí ho vo vstavanom serveri PHP na `SIMULATOR_URL`. Proti nemu beží každý [príklad](/sk/examples/);
[README simulátora](https://github.com/tomaj/payment-simulator#readme) popisuje Docker a všetky adresy.

<<< @/../examples/tatrapay/run.php#config

Čo simulátor nerobí:

- pri návratoch z TatraPay a CardPay neposiela `TID`; `getTransactionReference()` nastaví iba `completeAuthorize()`
  s `tpay` `Y`;
- neodpovedá za webovú službu ComfortPay; tieto požiadavky v testovacom režime vybaví knižnica lokálne, pozrite nižšie.

## Webová služba ComfortPay v testovacom režime {#comfortpay-web-service-in-test-mode}

Požiadavky SOAP vracajú pevné odpovede podľa parity ID, takže otestujete obe vetvy svojho kódu:

| Požiadavka | Párne ID | Nepárne ID |
|---|---|---|
| `charge()` (`referedCardId`) | zamietnutá, stav `02` | schválená, stav `00` |
| `checkTransaction()` (`transactionId`) | zamietnutá, stav `02` | schválená, stav `00` |
| `checkCard()` (`cardId`) | `OK` | `FAIL` |
| `unRegisterCard()` (`cardId`) | neodstránená | odstránená |

`listOfExpired()` vráti karty `12345` a `12346`; `listOfExpirePerId()` vráti každú požadovanú kartu s expiráciou
`0199`.
