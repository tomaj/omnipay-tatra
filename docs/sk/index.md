---
layout: home
title: omnipay-tatra – platby Tatra banky pre Omnipay
titleTemplate: false
description: 'Platobné brány TatraPay, CardPay a ComfortPay pre Omnipay a PHP 8.4+: podpísané presmerovania, overené návraty, predautorizácia s dokončením a vrátením platby, opakované platby uloženou kartou a lokálny simulátor na testovanie.'

hero:
  name: omnipay-tatra
  text: Platby Tatra banky pre Omnipay
  tagline: 'TatraPay, CardPay a ComfortPay pre PHP 8.4+. Požiadavky sa podpisujú vaším kľúčom, návrat zákazníka sa overí skôr, než tovar odošlete, a každý príklad na tejto stránke beží v CI proti lokálnemu simulátoru platobnej brány.'
  image:
    light: /illustrations/hero-sk-light.svg
    dark: /illustrations/hero-sk-dark.svg
    alt: E-shop presmeruje zákazníka do Tatra banky s podpísanou požiadavkou; banka zákazníka vráti s podpísaným výsledkom, ktorý overí completePurchase()
  actions:
    - theme: brand
      text: Začíname
      link: /sk/guide/getting-started
    - theme: alt
      text: Príklady
      link: /sk/examples/
    - theme: alt
      text: GitHub
      link: https://github.com/tomaj/omnipay-tatra

features:
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><rect class="ot-soft" x="2" y="2" width="44" height="44" rx="10"/><path class="ot-ink" d="M9 19L24 10l15 9M12 21v14M20 21v14M28 21v14M36 21v14M8 38h32"/><circle class="ot-acc" cx="38" cy="12" r="6"/></svg>'
    title: TatraPay
    details: Zákazník platí z internet bankingu Tatra banky. Jedno podpísané presmerovanie, jeden overený návrat a stav „čaká sa“, keď banka nestihne odpovedať.
    link: /sk/guide/tatrapay
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><rect class="ot-acc" x="5" y="11" width="38" height="26" rx="5"/><path class="ot-ink" d="M5 18h38"/><path class="ot-mark" d="M11 30h10"/><path class="ot-ink" d="M31 29l3 3 6-7"/></svg>'
    title: CardPay
    details: Platby kartou s údajmi pre 3-D Secure, predautorizácia a server-to-server dokončenie, zrušenie predautorizácie a vrátenie platby.
    link: /sk/guide/cardpay
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><rect class="ot-acc" x="4" y="13" width="28" height="19" rx="4"/><path class="ot-ink" d="M4 19h28"/><path class="ot-ink" d="M38 16a12 12 0 0 1-4 20M33 33l1 4 4-1M44 24"/><circle class="ot-ink-f" cx="40" cy="12" r="2.5"/></svg>'
    title: ComfortPay
    details: Kartu zaregistrujte pri platbe a ďalšie platby z nej strhávajte z cronu cez webovú službu SOAP, s typovanými zamietnutiami a timeoutmi.
    link: /sk/guide/comfortpay
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><path class="ot-soft" d="M24 3l17 6v13c0 11-7 19-17 23C14 41 7 33 7 22V9z"/><circle class="ot-acc" cx="20" cy="22" r="6"/><path class="ot-ink" d="M25 24h12M33 24v5M37 24v4"/></svg>'
    title: HMAC, AES a DES
    details: Algoritmus určuje dĺžka vášho kľúča. Každé pole, ktoré banka podpisuje, je zdokumentované a príklad podpis overuje proti openssl CLI.
    link: /sk/guide/signatures
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><rect class="ot-soft" x="3" y="6" width="30" height="36" rx="6"/><path class="ot-ink" d="M10 16h16M10 23h12M10 30h9"/><circle class="ot-acc" cx="35" cy="33" r="10"/><path class="ot-mark" d="M30.5 33.5l3.2 3 6-6.5"/></svg>'
    title: Overené návraty
    details: completePurchase() odmietne podvrhnutý výsledok, nesprávny podpis aj variabilný symbol inej objednávky skôr, než čokoľvek označíte ako zaplatené.
    link: /sk/guide/payment-flow
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><rect class="ot-soft" x="3" y="5" width="42" height="30" rx="6"/><path class="ot-ink" d="M10 14l6 5-6 5M20 25h9M17 41h14M24 35v6"/><circle class="ot-acc" cx="37" cy="15" r="4"/></svg>'
    title: Testovanie bez banky
    details: Testovací režim posiela platby do simulátora platobnej brány payment-simulator, ktorý odpovede podpisuje ako banka. Spustite ho lokálne a otestujte každú vetvu.
    link: /sk/guide/test-mode
---

## Ako to vyzerá {#what-it-looks-like}

Presmerujte zákazníka do banky a potom overte návrat. Toto je [TatraPay od začiatku do konca](/sk/examples/tatrapay), ktorý v CI beží proti lokálnemu simulátoru:

<<< @/../examples/tatrapay/run.php#purchase

<<< @/../examples/tatrapay/run.php#complete

Jeho výstup: simulátor prijme podpis, každá z odpovedí banky sa overí a upravené alebo cudzie spätné volanie sa odmietne.

<<< @/../examples/tatrapay/expected-output.txt{text}

## Kam ďalej {#where-to-go-next}

<div class="ot-cards">
  <a href="./guide/getting-started"><strong>Začíname</strong><span>Nainštalujte balík, presmerujte zákazníka a overte jeho návrat.</span></a>
  <a href="./guide/payment-flow"><strong>Ako prebieha platba</strong><span>Presmerovanie, návrat a čo sa v ktorom kroku overuje.</span></a>
  <a href="./examples/"><strong>Príklady</strong><span>Šesť kompletných receptov, od platby cez TatraPay po predplatné cez ComfortPay.</span></a>
  <a href="./guide/testing"><strong>Testovanie integrácie</strong><span>Simulátor, testovací režim a unit testy s podpísanými spätnými volaniami.</span></a>
  <a href="./api/reference"><strong>Referencia API</strong><span>Každá brána, request, response, enum a výnimka.</span></a>
  <a href="./security"><strong>Bezpečnosť</strong><span>Čo knižnica overí za vás a čo musíte skontrolovať sami.</span></a>
</div>
