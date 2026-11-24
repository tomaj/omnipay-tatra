---
title: Podpisy
description: Čo presne omnipay-tatra podpisuje pri každej požiadavke a návrate z Tatra banky, s kľúčmi HMAC-SHA256, AES a DES.
---

# Podpisy

Každá požiadavka do Tatra banky a každá odpoveď z nej nesie podpis nad spojením polí (bez oddeľovačov, prázdne polia
neprispievajú ničím). Algoritmus určuje [dĺžka kľúča](./configuration#the-key-selects-the-algorithm). Prijaté podpisy
sa porovnávajú cez `hash_equals()`; chýbajúci, prázdny alebo nereťazcový podpis sa nikdy nezhoduje.

## Algoritmy {#algorithms}

### HMAC-SHA256 {#hmac-sha256}

`hash_hmac('sha256', $data, hex2bin($key))` so 128-znakovým hexadecimálnym kľúčom (64 bajtov); výstup v malých
hexadecimálnych znakoch v poli `HMAC`. Požiadavky s HMAC nesú aj `TIMESTAMP`, čas požiadavky v UTC vo formáte
`ddmmYYYYHHMMSS`.

```bash
printf '%s' "$DATA" | openssl dgst -sha256 -mac HMAC -macopt hexkey:$KEY
```

### AES {#aes}

So 64-znakovým hexadecimálnym kľúčom: prvých 16 bajtov `SHA-1($data)` zašifrovaných cez AES v režime ECB, prvých
32 hexadecimálnych znakov veľkými písmenami, v poli `SIGN`.

Knižnica šifruje **cez AES-128 a prvých 16 bajtov 32-bajtového kľúča**; druhá polovica kľúča nemá žiadny vplyv. Robí
to od verzie 0.2.1 (2017), keď podpisovanie prešlo z mcrypt na OpenSSL. Predtým mcrypt podľa 32-bajtového kľúča zvolil
AES-256, rovnako ako vtedajšie referenčné knižnice. Špecifikácia režimu AES od Tatra banky nie je verejná, preto výstup
ostáva taký, aký je, kým banka nepotvrdí, ktorý očakáva; pozrite
[#34](https://github.com/tomaj/omnipay-tatra/issues/34). Uprednostnite kľúč HMAC.

```bash
printf '%s' "$DATA" | openssl dgst -sha1 -binary | head -c 16 \
  | openssl enc -aes-128-ecb -K "${KEY:0:32}" | xxd -p | head -c 32 | tr a-f A-F
```

### DES {#des}

S 8-znakovým kľúčom: `SHA-1($data)` zašifrovaný cez DES v režime ECB, prvých 16 hexadecimálnych znakov veľkými
písmenami, v poli `SIGN`. OpenSSL 3 ponúka DES iba cez svoj legacy provider; bez neho podpisovanie vyhodí
`Omnipay\Core\Exception\SignatureException`.

```bash
printf '%s' "$DATA" | openssl dgst -sha1 -binary \
  | openssl enc -des-ecb -provider legacy -provider default -K "$(printf '%s' "$KEY" | xxd -p)" | xxd -p | head -c 16 | tr a-f A-F
```

## Podpisované polia {#signed-fields}

`CURR` je číselný kód meny (`978` pre EUR).

| Správa | HMAC | AES / DES |
|---|---|---|
| TatraPay `purchase()` | `MID AMT CURR VS SS CS RURL REM TIMESTAMP` | `MID AMT CURR VS SS CS RURL` |
| Návrat z TatraPay | `AMT CURR VS SS CS RES TID TIMESTAMP` | `VS SS RES` |
| CardPay `purchase()` | `MID AMT CURR VS RURL IPC NAME REM TIMESTAMP` | `MID AMT CURR VS CS RURL IPC NAME` |
| Návrat z CardPay | `AMT CURR VS RES AC TRES CC RC TID TIMESTAMP` | `VS RES AC` |
| CardPay `authorize()` | `MID AMT CURR VS E2E TXN RURL IPC NAME REM TPAY CID ECID TDS_CARDHOLDER TDS_EMAIL TDS_MOBILE_PHONE TDS_BILL_CITY TDS_BILL_COUNTRY TDS_BILL_ADDRESS1 TDS_BILL_ADDRESS2 TDS_BILL_ZIP TDS_SHIP_CITY TDS_SHIP_COUNTRY TDS_SHIP_ADDRESS1 TDS_SHIP_ADDRESS2 TDS_SHIP_ZIP TDS_ADDR_MATCH TIMESTAMP` | - |
| CardPay `completeAuthorize()` | `AMT CURR VS TXN RES AC TRES CID CC RC TID TIMESTAMP` | - |
| Požiadavka CardPay `cancelAuthorize()` | `MID AMT TID VS TXN REM TIMESTAMP` | - |
| Odpoveď na CardPay `cancelAuthorize()` | telo XML, `HMAC=` v hlavičke `Authorization` | - |
| ComfortPay `purchase()` | `MID AMT CURR VS RURL IPC NAME REM TPAY TIMESTAMP` | `MID AMT CURR VS CS RURL IPC NAME TPAY` |
| Návrat z ComfortPay | `AMT CURR VS RES AC TRES CID CC RC TID TIMESTAMP` | `VS TRES AC CID` |

Pri návrate knižnica podpisuje **očakávaný** `VS` (a `SS`, `CS`, `AMT`, `CURR`), ktorý odovzdáte požiadavke, nie
hodnoty z query stringu, a odmietne callback, ktorého `VS` sa líši. Ostatné polia pochádzajú z query stringu.

## Overte si podpis sami {#check-a-signature-yourself}

[Príklad s podpismi](/sk/examples/signatures) zostaví požiadavku TatraPay s každým typom kľúča a porovná podpis
knižnice s vyššie uvedenými príkazmi `openssl`; CI ho spúšťa pri každej zmene.

## Čo musíte overiť vy {#what-you-still-check}

- Sumu pri kľúčoch AES a DES: ich podpisy pri návrate ju nepokrývajú.
- Že objednávka sa zaplatí len raz: zákazník môže návratovú URL otvoriť znova.
- Webová služba ComfortPay sa autentifikuje cez TLS vaším klientskym certifikátom, nie týmito podpismi.
