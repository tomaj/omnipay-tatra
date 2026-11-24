---
title: Testovanie integrácie
description: Testovanie integrácie s Tatra bankou bez banky - payment-simulator od začiatku do konca, unit testy návratovej URL s podpísanými callbackmi, mockovaný HTTP klient pre cancelAuthorize() a pripravený SOAP klient pre ComfortPay.
---

# Testovanie integrácie

Platobná integrácia má tri časti, ktoré sa oplatí testovať, každú na inej úrovni:

| Čo | Ako | Potrebuje |
|---|---|---|
| presmerovanie a návrat od začiatku do konca | [testovací režim](./test-mode) proti simulátoru platobnej brány payment-simulator | bežiaci simulátor |
| váš handler návratovej URL: zaplatené, nezaplatené, podvrhnuté, cudzie | unit testy s callbackom, ktorý si podpíšete sami | nič |
| dokončenie predautorizácie, vrátenie platby a platby ComfortPay | mockovaný HTTP klient alebo SOAP klient | nič |

## Od začiatku do konca s payment-simulator {#end-to-end-with-payment-simulator}

Presne toto robia [príklady](/sk/examples/) tohto balíka a CI ich spúšťa pri každej zmene: platba sa presmeruje na
lokálny simulátor a podpísané odpovede simulátora sa nasledujú späť a overia. [Príklad e-shopu](/sk/examples/shop)
navyše spúšťa skutočný handler návratovej URL na vstavanom PHP serveri.

Vo vlastnom CI spustite simulátor vedľa svojej aplikácie a nasmerujte naň `testHost`:

```yaml
- name: Start payment-simulator
  run: |
    git clone --depth 1 https://github.com/tomaj/payment-simulator.git "$RUNNER_TEMP/payment-simulator"
    composer install --working-dir="$RUNNER_TEMP/payment-simulator" --no-dev --no-progress
    php -S 127.0.0.1:8080 -t "$RUNNER_TEMP/payment-simulator/www" "$RUNNER_TEMP/payment-simulator/www/index.php" &
```

[`examples/ci/start-simulator.sh`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/ci/start-simulator.sh)
robí to isté a počká, kým simulátor odpovedá. Stránky simulátora sú obyčajné HTML: otvorte URL presmerovania,
skontrolujte `Sign OK` a nasledujte odkaz `continue ok`, `continue fail` alebo `continue timeout`, ako to robí
[`examples/_support/simulator.php`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/_support/simulator.php).

## Unit testy návratovej URL {#unit-tests-of-the-return-url}

Návrat je GET požiadavka s podpísaným query stringom, takže ju test vie zostaviť: podpíšte polia podpisovačom knižnice
a svojím testovacím kľúčom, vložte ich do Symfony `Request` a s ním vytvorte bránu. Pokryte aspoň štyri vetvy:
zaplatené, nezaplatené, upravený výsledok a callback inej objednávky.

<<< @/snippets/testing-callback.php#snippet

Podpisujte v poradí polí pre váš typ kľúča podľa stránky [Podpisy](./signatures#signed-fields). Do testu nikdy
nedávajte produkčný kľúč; postačí ľubovoľný 128-znakový hexadecimálny reťazec.

## Dokončenie a vrátenie platby s mockovaným HTTP klientom {#capture-and-refund-with-a-mocked-http-client}

`cancelAuthorize()` posiela požiadavku cez HTTP klienta Omnipay. Odovzdajte bráne klienta postaveného na
`php-http/mock-client` a zaraďte doň odpoveď banky; požiadavku, ktorú knižnica odoslala, získate z mocku pre asercie.

<<< @/snippets/cardpay-authorize.php#capture

Celé nastavenie je v [`docs/snippets/cardpay-authorize.php`](https://github.com/tomaj/omnipay-tatra/blob/master/docs/snippets/cardpay-authorize.php):
`new Gateway(new \Omnipay\Common\Http\Client($mock), $request)`. Podpísaná odpoveď potrebuje hlavičku
`Authorization: HMAC=...` nad telom XML; bez hlavičky sa telo prijme tak, ako je.

## ComfortPay s pripraveným SOAP klientom {#comfortpay-with-a-canned-soap-client}

V testovacom režime sa požiadavky webovej služby k banke nikdy nedostanú a odpovedajú podľa parity ID (pozrite
[testovací režim](./test-mode#comfortpay-web-service-in-test-mode)), čo na otestovanie vašich vetiev stačí. Ak chcete
otestovať skutočné spracovanie požiadavky a odpovede, dajte bráne `SoapClient`, ktorý odpovedá pripraveným XML:

<<< @/snippets/testing-soap.php#snippet

Zamietnutie je chyba SOAP s `SOAP-ENV:Receiver` a `errorCode` v detaile; vlastné testy balíka ich zostavujú
v [`tests/Support/FakeSoapClient.php`](https://github.com/tomaj/omnipay-tatra/blob/master/tests/Support/FakeSoapClient.php).

## Pred spustením do produkcie {#before-you-go-live}

- Vypnite `testMode` a použite produkčný `mid` a kľúč od Tatra banky; do repozitára ich nedávajte.
- Pre každú bránu a typ kľúča, ktorý používate, urobte jednu skutočnú platbu s malou sumou a vráťte ju.
- Overte, že opätovné načítanie návratovej URL neodošle objednávku dvakrát a že odmietnutý callback nič nezmení.
