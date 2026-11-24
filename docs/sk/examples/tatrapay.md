---
title: Platba cez TatraPay od začiatku do konca
description: Kompletná platba cez TatraPay proti simulátoru payment-simulator - podpísané presmerovanie, každá odpoveď banky overená cez completePurchase() a odmietnutý podvrhnutý aj cudzí návrat z banky.
---

# Platba cez TatraPay od začiatku do konca

Zákazník platí z internet bankingu Tatra banky. Príklad pošle platbu TatraPay s HMAC kľúčom na
[payment-simulator](/sk/guide/test-mode#payment-simulator), prejde každou z troch odpovedí, ktoré môže banka poslať
na návratovú URL, a overí ju. Potom skúsi dva útoky a zaplatí so špecifickým symbolom s HMAC aj AES kľúčom.

Zdrojový kód: [`examples/tatrapay/run.php`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/tatrapay/run.php) ·
spustíte ho príkazom `examples/ci/example.sh tatrapay`.

## 1. Nastavenie brány {#_1-configure-the-gateway}

<<< @/../examples/tatrapay/run.php#config

`testMode` pošle zákazníka na `testHost` namiesto `https://moja.tatrabanka.sk`. Testovací HMAC kľúč simulátora je
128-krát `1`; po podpísaní zmluvy dostanete vlastný `mid` a kľúč.

## 2. Presmerovanie zákazníka {#_2-redirect-the-customer}

<<< @/../examples/tatrapay/run.php#purchase

`purchase()` nikam nevolá: len zostaví podpísanú URL. Vo webovej požiadavke zavolajte `$response->redirect()`, ktorá
pošle `302` a skončí. Príklad namiesto toho vypíše polia:

```text
redirect: http://127.0.0.1:8080/payment/tatrapay-hmac
  PT        TatraPay
  MID       9999
  AMT       24.90
  CURR      978
  ...
  HMAC      64 hex characters
```

`CURR` je číselný kód meny podľa ISO 4217. Okrem týchto polí nesie URL aj `TIMESTAMP` (aktuálny čas v UTC, podpísaný)
a prázdne nepovinné polia.

## 3. Overenie návratu {#_3-verify-the-return}

Simulátor odpovedá ako banka: `continue ok` (`RES=OK`), `continue fail` (`RES=FAIL`) a `continue timeout`
(`RES=TOUT`), každá je podpísaný odkaz na `rurl`. Na návratovej URL e-shop zavolá `completePurchase()` s objednávkou,
ktorú očakáva:

<<< @/../examples/tatrapay/run.php#complete

Vo webovej aplikácii `Omnipay::create('TatraPay')` načíta aktuálnu požiadavku; príklad namiesto toho odovzdá
požiadavku zákazníka do konštruktora brány, pretože jeden skript hrá obe strany.

## 4. Útoky, ktoré neprejdú {#_4-attacks-that-fail}

<<< @/../examples/tatrapay/run.php#forged

- Upravený výsledok neprejde overením podpisu: `RES` je súčasťou podpisovaného reťazca.
- Správne podpísaná odpoveď pre objednávku `2026001` sa neprijme pre objednávku `2026002`: vrátený `VS` musí byť
  ten, ktorý očakávate.

## 5. So špecifickým symbolom {#_5-with-a-specific-symbol}

<<< @/../examples/tatrapay/run.php#specific-symbol

`ss` sa posiela ako `SS` a v požiadavke sa podpisuje (`MID AMT CURR VS SS CS RURL …`). Banka ho pošle späť a podpisuje
nad ním aj návrat: `VS SS RES` pri AES a DES kľúčoch, `AMT CURR VS SS CS RES TID TIMESTAMP` pri HMAC kľúčoch, ako to
určuje technická príručka TatraPay od Tatra banky. Do `completePurchase()` odovzdajte rovnaký `ss`; bez neho overenie
podpisu zlyhá.

## Výstup {#output}

<<< @/../examples/tatrapay/expected-output.txt{text}

## Čo príklad testuje {#what-it-tests}

- simulátor prijme podpis požiadavky (`Sign OK`), takže polia aj ich poradie zodpovedajú banke;
- `isSuccessful()` pre `OK`, `isPending()` pre `TOUT`, ani jedno pre `FAIL`;
- `InvalidRequestException` pre upravený výsledok aj pre návrat z banky patriaci inej objednávke;
- špecifický symbol s HMAC a AES kľúčom: simulátor prijme požiadavku, knižnica návrat, a bez očakávaného `ss` návrat
  overením neprejde.

Ďalej: [CardPay s HMAC a AES kľúčom](./cardpay) alebo ten istý postup v skutočnom [e-shope](./shop).
