---
layout: home
title: omnipay-tatra - Tatra banka payments for Omnipay
titleTemplate: false
description: 'TatraPay, CardPay and ComfortPay gateways for Omnipay and PHP 8.4+: signed redirects, verified returns, pre-authorization with capture and refund, card-on-file charges, and a local simulator to test against.'

hero:
  name: omnipay-tatra
  text: Tatra banka payments for Omnipay
  tagline: 'TatraPay, CardPay and ComfortPay for PHP 8.4+. Requests are signed with your key, the customer''s return is verified before you ship, and every example on this site runs against a local payment simulator in CI.'
  image:
    light: /illustrations/hero-light.svg
    dark: /illustrations/hero-dark.svg
    alt: The shop redirects the customer to Tatra banka with a signed request; the bank sends the customer back with a signed result that completePurchase() verifies
  actions:
    - theme: brand
      text: Get started
      link: /guide/getting-started
    - theme: alt
      text: Examples
      link: /examples/
    - theme: alt
      text: GitHub
      link: https://github.com/tomaj/omnipay-tatra

features:
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><rect class="ot-soft" x="2" y="2" width="44" height="44" rx="10"/><path class="ot-ink" d="M9 19L24 10l15 9M12 21v14M20 21v14M28 21v14M36 21v14M8 38h32"/><circle class="ot-acc" cx="38" cy="12" r="6"/></svg>'
    title: TatraPay
    details: The customer pays from Tatra banka internet banking. One signed redirect, one verified return, with a pending state for bank timeouts.
    link: /guide/tatrapay
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><rect class="ot-acc" x="5" y="11" width="38" height="26" rx="5"/><path class="ot-ink" d="M5 18h38"/><path class="ot-mark" d="M11 30h10"/><path class="ot-ink" d="M31 29l3 3 6-7"/></svg>'
    title: CardPay
    details: Card payments with 3-D Secure data, pre-authorization, and the server-to-server capture, release and refund.
    link: /guide/cardpay
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><rect class="ot-acc" x="4" y="13" width="28" height="19" rx="4"/><path class="ot-ink" d="M4 19h28"/><path class="ot-ink" d="M38 16a12 12 0 0 1-4 20M33 33l1 4 4-1M44 24"/><circle class="ot-ink-f" cx="40" cy="12" r="2.5"/></svg>'
    title: ComfortPay
    details: Register the card during a payment, then charge it from a cron job through the SOAP web service, with typed declines and timeouts.
    link: /guide/comfortpay
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><path class="ot-soft" d="M24 3l17 6v13c0 11-7 19-17 23C14 41 7 33 7 22V9z"/><circle class="ot-acc" cx="20" cy="22" r="6"/><path class="ot-ink" d="M25 24h12M33 24v5M37 24v4"/></svg>'
    title: HMAC, AES and DES
    details: The length of your key selects the algorithm. Every field the bank signs is documented, and an example checks it against the openssl CLI.
    link: /guide/signatures
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><rect class="ot-soft" x="3" y="6" width="30" height="36" rx="6"/><path class="ot-ink" d="M10 16h16M10 23h12M10 30h9"/><circle class="ot-acc" cx="35" cy="33" r="10"/><path class="ot-mark" d="M30.5 33.5l3.2 3 6-6.5"/></svg>'
    title: Verified returns
    details: completePurchase() rejects a forged result, a wrong signature or another order's variable symbol before you mark anything paid.
    link: /guide/payment-flow
  - icon: '<svg class="ot-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="40" height="40" aria-hidden="true" focusable="false"><rect class="ot-soft" x="3" y="5" width="42" height="30" rx="6"/><path class="ot-ink" d="M10 14l6 5-6 5M20 25h9M17 41h14M24 35v6"/><circle class="ot-acc" cx="37" cy="15" r="4"/></svg>'
    title: Test without the bank
    details: Test mode sends payments to payment-simulator, which signs its answers like the bank. Run it locally and test every branch.
    link: /guide/test-mode
---

## What it looks like

Redirect the customer to the bank, then verify the return. This is the [TatraPay round trip](/examples/tatrapay), which runs in CI against a local simulator:

<<< @/../examples/tatrapay/run.php#purchase

<<< @/../examples/tatrapay/run.php#complete

Its output: the simulator accepts the signature, each of the bank's answers is verified, and an edited or foreign callback is rejected.

<<< @/../examples/tatrapay/expected-output.txt{text}

## Where to go next

<div class="ot-cards">
  <a href="./guide/getting-started"><strong>Getting started</strong><span>Install the package, redirect the customer and verify the return.</span></a>
  <a href="./guide/payment-flow"><strong>How a payment works</strong><span>The redirect, the return and what is verified at each step.</span></a>
  <a href="./examples/"><strong>Examples</strong><span>Six complete recipes, from a TatraPay round trip to a ComfortPay subscription.</span></a>
  <a href="./guide/testing"><strong>Testing your integration</strong><span>The simulator, test mode and unit tests with signed callbacks.</span></a>
  <a href="./api/reference"><strong>API reference</strong><span>Every gateway, request, response, enum and exception.</span></a>
  <a href="./security"><strong>Security</strong><span>What the library verifies for you and what you still check.</span></a>
</div>
