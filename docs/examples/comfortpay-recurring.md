---
title: ComfortPay subscription
description: A monthly subscription with ComfortPay - the first payment registers the card at payment-simulator, later months charge it through the web service, and the card is removed when the customer cancels.
---

# ComfortPay subscription

ComfortPay is CardPay that registers the card. The first payment goes through the bank's page like any CardPay payment
and returns a card id (`CID`); from then on your server charges that card through Tatra banka's SOAP web service, for
example once a month from a cron job.

Source: [`examples/comfortpay-recurring/run.php`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/comfortpay-recurring/run.php) ·
run it with `examples/ci/example.sh comfortpay-recurring`.

<figure class="ot-diagram">
  <img class="light-only" src="/diagrams/flow-comfortpay-light.svg" alt="Sequence: purchase() with TPAY=Y redirects to the bank, the bank returns RES=OK with the card id CID, completePurchase() verifies and stores the CID; later charge() calls the SOAP web service with the CID and the client certificate and gets transaction status 00" width="760" height="454">
  <img class="dark-only" src="/diagrams/flow-comfortpay-dark.svg" alt="Sequence: purchase() with TPAY=Y redirects to the bank, the bank returns RES=OK with the card id CID, completePurchase() verifies and stores the CID; later charge() calls the SOAP web service with the CID and the client certificate and gets transaction status 00" width="760" height="454">
</figure>

## Configuration

<<< @/../examples/comfortpay-recurring/run.php#config

The redirect payment uses `mid` and `sharedSecret` (HMAC or AES; ComfortPay does not support DES). The web service uses
`ws`, `terminalId` and your client certificate. In test mode the web service requests are answered locally and the
certificate is not read.

## 1. Register the card with the first payment

<<< @/../examples/comfortpay-recurring/run.php#register

ComfortPay's `purchase()` always sends `TPAY=Y` and `AREDIR=1`.

## 2. Keep the card id

<<< @/../examples/comfortpay-recurring/run.php#return

Store only the `CID`. The masked number (`getCc()`) is fine to show the customer; the card number never reaches you.

## 3. Charge the card every month

<<< @/../examples/comfortpay-recurring/run.php#charge

`transactionId` is your unique id of the charge: use it to ask for the result later and never reuse it (the bank
answers `50054`, duplicate transaction). Identify the payment with both `vs` and `ss`, or with `e2eReference`.

In test mode an even card id is declined (`transactionStatus` `02`) and an odd one approved, so both branches of your
code can run; the simulator's card id `111111` is odd.

## 4. Check and manage the card

<<< @/../examples/comfortpay-recurring/run.php#manage

`listOfExpired(['expDate' => '2026-12-01'])` lists the registered cards that expire in that month (the WSDL says the
day is ignored), so you can ask those customers for a new card before a charge fails.

## Output

<<< @/../examples/comfortpay-recurring/expected-output.txt{text}

## What it tests

- the simulator accepts the ComfortPay request (`TPAY=Y` signed) and returns a registered card id that
  `completePurchase()` verifies;
- `charge()` builds a valid web service request for an approved and a declined card;
- `checkTransaction()`, `checkCard()`, `listOfExpirePerId()` and `unRegisterCard()` return typed results.

In production, see [Error handling](/guide/errors#comfortpay-web-service) for declines, timeouts and SOAP faults, and
[TLS and proxy](/guide/comfortpay#tls-and-proxy) for the connection.
