---
layout: home

hero:
  name: omnipay-tatra
  text: Tatra banka payments for Omnipay
  tagline: TatraPay, CardPay and ComfortPay gateways for PHP 8.4+, with signed requests and verified callbacks.
  image:
    src: /logo.svg
    alt: omnipay-tatra
  actions:
    - theme: brand
      text: Get started
      link: /guide/getting-started
    - theme: alt
      text: API reference
      link: /api/reference
    - theme: alt
      text: GitHub
      link: https://github.com/tomaj/omnipay-tatra

features:
  - title: TatraPay
    details: The customer pays from Tatra banka internet banking. Redirect with a signed request, verify the signed return.
    link: /guide/tatrapay
  - title: CardPay
    details: Card payments, pre-authorization with 3-D Secure data, and the server-to-server capture, reversal and refund.
    link: /guide/cardpay
  - title: ComfortPay
    details: Register the card during a payment, then charge it later through the bank's SOAP web service.
    link: /guide/comfortpay
  - title: HMAC, AES and DES
    details: The signature algorithm follows the length of your key. Bank signatures are compared in constant time.
    link: /guide/signatures
  - title: Verified callbacks
    details: completePurchase() checks the signature and that the bank returned the variable symbol of your order.
    link: /guide/getting-started#verify-the-return
  - title: Test mode
    details: Send payments to a simulator instead of the bank, or to your own payment-simulator with testHost.
    link: /guide/test-mode
---
