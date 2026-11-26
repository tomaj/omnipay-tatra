---
title: Bezpečnosť
description: Čo omnipay-tatra overí za vás, čo musíte skontrolovať sami pred odoslaním objednávky, ako chrániť kľúče a ako nahlásiť zraniteľnosť.
---

# Bezpečnosť

Platobnú integráciu útočník napáda tam, kde údaje prenáša prehliadač zákazníka: pri presmerovaní do banky a najmä pri
návrate do vášho e-shopu. Táto stránka uvádza, čo s tým robí knižnica a čo ostáva na vás.

## Nahlásenie zraniteľnosti {#reporting-a-vulnerability}

**Neotvárajte** verejné issue. Nahláste ju súkromne cez
[GitHub private vulnerability reporting](https://github.com/tomaj/omnipay-tatra/security/advisories/new) alebo
e-mailom, ako popisuje [SECURITY.md](https://github.com/tomaj/omnipay-tatra/blob/master/SECURITY.md), kde sú uvedené
aj podporované verzie. Opravy sa zverejňujú ako [GitHub Security Advisories](https://github.com/tomaj/omnipay-tatra/security/advisories).

## Čo knižnica overuje {#what-the-library-verifies}

| Hrozba | Ochrana |
|---|---|
| zákazník upraví návratovú URL (`RES=FAIL` na `RES=OK`) | výsledok je podpísaný; `completePurchase()` vyhodí `InvalidRequestException` ([príklad](/sk/examples/tatrapay)) |
| platný callback lacnej objednávky sa zopakuje pre drahú | vrátený `VS` sa musí rovnať `vs`, ktorý odovzdáte; očakávaný VS je súčasťou podpisovaného reťazca |
| callback na inú sumu (kľúče HMAC) | podpisovaný reťazec sa skladá z **vašich** hodnôt `amount` a `currency` |
| hádanie podpisu bajt po bajte | podpisy sa porovnávajú cez `hash_equals()`; chýbajúci `HMAC`/`SIGN` alebo pole namiesto reťazca sa nikdy nezhoduje |
| podvrhnutá odpoveď na dokončenie predautorizácie alebo vrátenie platby | odpoveď XML z `cancelAuthorize()` sa overí voči `HMAC` v hlavičke `Authorization` |
| man-in-the-middle na webovej službe ComfortPay | TLS s overením certifikátu a názvu hostiteľa, voči systémovému úložisku CA alebo vášmu `caFile` |
| meno zákazníka, ktoré banka po presmerovaní odmietne | `name` sa overí pred presmerovaním |

## Čo musíte overiť vy {#what-you-still-check}

- **Sumu pri kľúčoch AES a DES.** Ich podpisy pri návrate pokrývajú iba VS a výsledok (pri CardPay aj autorizačný
  kód). Sumu objednávky porovnajte sami, alebo lepšie, požiadajte Tatra banku o kľúč HMAC.
- **Že objednávka sa zaplatí len raz.** Zákazník môže návratovú URL načítať znova a overí sa opäť. Objednávku meňte
  iba pri jej prvom úspešnom návrate ([príklad e-shopu](/sk/examples/shop#the-return-url)).
- **Že `TOUT` nie je `OK`.** Timeout pri TatraPay je `isPending()`, nie zaplatené.
- **Že `FAIL` z `cancelAuthorize()` nie je dôkaz.** Nepodpísaná odpoveď `FAIL` sa prijme; berte ju ako „nevykonané“.
- **Že v produkcii je testovací režim vypnutý.** S `testMode` vedie presmerovanie na simulátor, ktorý podpisuje
  verejnými kľúčmi.

## Kľúče a certifikáty {#keys-and-certificates}

- `sharedSecret`, `certPath` a `certPass` načítajte z prostredia alebo z úložiska tajomstiev; nikdy ich necommitujte.
  [Príklad e-shopu](/sk/examples/shop) číta `TATRA_SHARED_SECRET` a verejný testovací kľúč použije iba pre simulátor.
- Súbor s klientskym certifikátom ComfortPay sprístupnite iba používateľovi, pod ktorým beží vaše PHP.
- Overovanie TLS pre webovú službu nikdy nevypínajte. Ak systémové úložisko CA banku neoverí, odovzdajte balík CA
  cez `caFile` alebo vlastný `streamContext` ([TLS a proxy](./guide/comfortpay#tls-and-proxy)).
- Uniknutý kľúč umožní komukoľvek podpisovať callbacky pre váš e-shop: okamžite požiadajte Tatra banku o nový.

## Tajomstvá v chybových hláseniach {#secrets-in-error-reports}

Zdieľaný kľúč a heslá pre ComfortPay sú označené `#[\SensitiveParameter]` všade, kde sú argumentom funkcie:
`setSharedSecret()`, `setCertPass()`, `setProxyPassword()`, `sign()` podpisovačov a `KeyType::fromSecret()`. Keď sa
vyhodí výnimka a niektoré z týchto volaní je na zásobníku, PHP v `getTraceAsString()` aj v chybových hláseniach
(Tracy, Sentry, Monolog) zobrazí namiesto hodnoty `Object(SensitiveParameterValue)`.

Atribút nepokrýva hodnoty odovzdané v poli ani uložené v objekte: `initialize(['sharedSecret' => ...])`,
`purchase([...])` či voľby, ktoré knižnica odovzdáva `SoapClient`, sa v trase zobrazia ako `Array` a brána alebo
požiadavka odovzdaná cez váš kód ako `Object(...)`; nástroje, ktoré vypisujú argumenty celé (BlueScreen v Tracy,
`print_r()` z `getTrace()`), v nich tajomstvá ukážu. V produkcii nastavte `zend.exception_ignore_args = On` (predvolená hodnota v `php.ini-production`): trasy
potom neobsahujú žiadne argumenty.

## Známe obmedzenia {#known-limitations}

- Podpis AES používa AES-128 a prvú polovicu 64-znakového kľúča, tak ako od roku 2017; či Tatra banka očakáva
  AES-256, nie je potvrdené ([#34](https://github.com/tomaj/omnipay-tatra/issues/34)). Pozrite
  [Podpisy](./guide/signatures#aes).
- DES je zastaraná šifra, v OpenSSL 3 dostupná iba cez legacy provider.
