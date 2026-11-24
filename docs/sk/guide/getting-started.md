---
title: Začíname
description: Nainštalujte omnipay-tatra, presmerujte zákazníka do Tatra banky a po jeho návrate overte platbu.
---

# Začíname

## Požiadavky {#requirements}

- PHP 8.4 alebo novšie s rozšíreniami `openssl`, `soap` a `simplexml`
- adaptér HTTP klienta, ktorý Omnipay vie nájsť, napríklad `php-http/guzzle7-adapter` (Guzzle 7)
- zmluva obchodníka s Tatra bankou: identifikátor obchodníka (`MID`) a tajný kľúč pre každú službu

## Inštalácia {#installation}

```bash
composer require tomaj/omnipay-tatra php-http/guzzle7-adapter
```

Balík zaregistruje tri platobné brány Omnipay: `TatraPay`, `CardPay` a `ComfortPay`. Vytvoríte ich cez
`Omnipay::create()` alebo `new \Omnipay\TatraPay\Gateway()`.

## Priebeh platby {#the-payment-flow}

Všetky brány fungujú rovnako:

1. **Purchase**: zostavte podpísanú požiadavku a presmerujte zákazníka do banky.
2. Zákazník zaplatí (alebo nie) a banka ho presmeruje späť na vašu návratovú URL (`rurl`) s výsledkom a podpisom
   v query stringu.
3. **Complete purchase**: na návratovej URL overte podpis a variabilný symbol a až potom označte objednávku ako zaplatenú.

[Ako prebieha platba](./payment-flow) vysvetľuje každý krok aj s diagramom.

## Presmerovanie zákazníka {#redirect-the-customer}

<<< @/snippets/tatrapay-purchase.php#snippet

`vs` (variabilný symbol, najviac 10 číslic) identifikuje objednávku. Uložte si ho k objednávke: budete ho potrebovať
na overenie návratu.

## Overenie návratu {#verify-the-return}

<<< @/snippets/tatrapay-complete.php#snippet

`completePurchase()` vyhodí `Omnipay\Common\Exception\InvalidRequestException`, keď je podpis nesprávny alebo keď banka
vrátila iný variabilný symbol než `vs`, ktorý odovzdáte. Platba prešla iba vtedy, keď odpoveď vráti `isSuccessful()`.

::: warning Porovnajte sumu
Pri HMAC kľúčoch banka podpisuje aj sumu, takže spätné volanie s inou sumou neprejde overením podpisu. Podpisy AES
a DES sumu nepokrývajú: pred odoslaním tovaru ju porovnajte s objednávkou.
:::

## Vyskúšajte to so simulátorom {#run-it-against-the-simulator}

Na vyskúšanie celého priebehu nepotrebujete zmluvu. Spustite lokálne
[payment-simulator](https://github.com/tomaj/payment-simulator), simulátor platobnej brány, ktorý prijíma požiadavky
podpísané jeho verejnými testovacími kľúčmi a odpovedá ako banka:

```bash
git clone https://github.com/tomaj/omnipay-tatra.git && cd omnipay-tatra
examples/ci/start-simulator.sh          # clones payment-simulator and serves it at http://127.0.0.1:8080
examples/ci/example.sh tatrapay         # composer install + run examples/tatrapay/run.php
```

[Príklad TatraPay](/sk/examples/tatrapay) presmeruje na simulátor, prejde každou z odpovedí banky späť a overí ju;
toto je jeho skutočný výstup, ktorý CI porovnáva pri každej zmene:

<<< @/../examples/tatrapay/expected-output.txt{text}

## Ďalšie kroky {#next-steps}

- [Konfigurácia](./configuration): všetky parametre brány a ako kľúč určuje algoritmus podpisu
- [TatraPay](./tatrapay), [CardPay](./cardpay), [ComfortPay](./comfortpay): všetky operácie jednotlivých služieb
- [Testovací režim](./test-mode): vyskúšajte priebeh platby bez banky
- [Príklady](/sk/examples/): kompletné recepty, od návratovej URL e-shopu po predplatné cez ComfortPay
- [Spracovanie chýb](./errors) a [Bezpečnosť](/sk/security) pred spustením do produkcie
