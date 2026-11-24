---
title: Konfigurácia
description: Parametre brán TatraPay, CardPay a ComfortPay a ako dĺžka tajného kľúča určuje algoritmus podpisu.
---

# Konfigurácia

Parametre brány nastavíte setterami alebo cez `initialize()`; brána ich odovzdá každej požiadavke, ktorú vytvorí.
Parametre požiadavky patria do `purchase([...])`, `completePurchase([...])` a ďalších metód požiadaviek.

## Parametre brány {#gateway-parameters}

| Parameter | Brány | Popis |
|---|---|---|
| `mid` | všetky | Identifikátor obchodníka (`MID`) od Tatra banky |
| `sharedSecret` | všetky | Kľúč od Tatra banky; jeho dĺžka určuje [algoritmus podpisu](#the-key-selects-the-algorithm) |
| `testMode` | všetky | Posiela požiadavky do simulátora namiesto banky, pozrite [Testovací režim](./test-mode) |
| `testHost` | všetky | Hostiteľ testovacích adries, predvolene `https://platby.tomaj.sk` |
| `ws` | ComfortPay | Identifikátor obchodníka pre webovú službu ComfortPay (`merchantId`) |
| `terminalId` | ComfortPay | Identifikátor terminálu webovej služby |
| `certPath` | ComfortPay | Cesta k PEM súboru s vaším klientskym certifikátom a privátnym kľúčom |
| `certPass` | ComfortPay | Heslo k privátnemu kľúču |
| `caFile` | ComfortPay | Balík CA certifikátov, ktorým sa overí certifikát webovej služby namiesto systémového úložiska CA |
| `streamContext` | ComfortPay | Vlastný `stream_context_create()` pre spojenie s webovou službou |
| `proxyHost`, `proxyPort`, `proxyLogin`, `proxyPassword` | ComfortPay | HTTP proxy pre webovú službu |
| `soapClient` | ComfortPay | Vlastný `SoapClient` (vlastný transport, testy) |

## Parametre požiadavky {#request-parameters}

| Parameter | Pole banky | Popis |
|---|---|---|
| `amount` | `AMT` | Suma ako reťazec s počtom desatinných miest podľa meny, `'10.00'` |
| `currency` | `CURR` | Kód ISO 4217, `'EUR'`; posiela sa ako číselný kód (`978`) |
| `vs` | `VS` | Variabilný symbol, najviac 10 číslic |
| `cs` | `CS` | Konštantný symbol, 4 číslice |
| `ss` | `SS` | Špecifický symbol, najviac 10 číslic (TatraPay) |
| `rurl` | `RURL` | Návratová URL adresa |
| `rem` | `REM` | E-mailová adresa pre notifikáciu o výsledku |
| `rsms` | `RSMS` | Číslo mobilného telefónu pre notifikáciu cez SMS |
| `desc` | `DESC` | Popis |
| `aredir` | `AREDIR` | Automatické presmerovanie späť (`true`/`false`) |
| `lang` | `LANG` | Jazyk stránky banky: `sk`, `en`, `de`, `hu`, `cz`, ... |
| `name` | `NAME` | Meno zákazníka (CardPay, ComfortPay): písmená bez diakritiky, číslice, medzera a `. - _ @` |
| `ipc` | `IPC` | IP adresa zákazníka (CardPay, ComfortPay); ak chýba, použije sa `REMOTE_ADDR` |
| `timestamp` | `TIMESTAMP` | Nastaví sa automaticky v UTC (`ddmmYYYYHHMMSS`), pri HMAC kľúčoch je podpísaná |

Settery prijímajú reťazce. `initialize()` a metódy požiadaviek prijímajú aj celé čísla a prevedú ich, takže
`'vs' => 123456` funguje.

## Kľúč určuje algoritmus {#the-key-selects-the-algorithm}

| Dĺžka kľúča | Algoritmus | Pole | Poznámky |
|---|---|---|---|
| 128 hexadecimálnych znakov | HMAC-SHA256 | `HMAC` | Súčasné kľúče Tatra banky; potrebné pre `authorize()` a `cancelAuthorize()` |
| 64 hexadecimálnych znakov | „AES256“ | `SIGN` | Pozrite [Podpisy](./signatures#aes) |
| 8 znakov | DES | `SIGN` | Starší algoritmus; potrebuje OpenSSL legacy provider; nie pre ComfortPay |

Kľúč inej dĺžky vyhodí `Omnipay\Core\Exception\UnsupportedKeyException`. Podľa kľúča sa líšia aj adresy: HMAC
požiadavky idú na `https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/tatrapay` alebo `.../cardpay`, AES a DES
požiadavky na `.../e-commerce.jsp`.

## Tajomstvá nepatria do kódu {#keep-secrets-out-of-the-code}

`sharedSecret`, `certPath` a `certPass` načítajte z premenných prostredia alebo z úložiska tajomstiev; nikdy ich
necommitujte.

```php
$gateway->setSharedSecret((string) getenv('TATRA_SHARED_SECRET'));
```
