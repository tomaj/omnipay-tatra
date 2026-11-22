---
title: Signatures
description: Exactly what omnipay-tatra signs for each Tatra banka request and callback, with HMAC-SHA256, AES and DES keys.
---

# Signatures

Every request to Tatra banka and every answer from it carries a signature over a concatenation of fields (no
separators, empty fields contribute nothing). The algorithm follows the [length of the key](./configuration#the-key-selects-the-algorithm).
Received signatures are compared with `hash_equals()`; a missing, empty or non-string signature never matches.

## Algorithms

### HMAC-SHA256

`hash_hmac('sha256', $data, hex2bin($key))` with the 128-character hexadecimal key (64 bytes); lowercase hex output in
the `HMAC` field. HMAC requests also carry `TIMESTAMP`, the request time in UTC as `ddmmYYYYHHMMSS`.

```bash
printf '%s' "$DATA" | openssl dgst -sha256 -mac HMAC -macopt hexkey:$KEY
```

### AES

With the 64-character hexadecimal key: the first 16 bytes of `SHA-1($data)` encrypted with AES in ECB mode, the first
32 hex characters in uppercase, in the `SIGN` field.

The library encrypts with **AES-128 and the first 16 bytes of the 32-byte key**; the second half of the key has no
effect. It has done so since 0.2.1 (2017), when the signer moved from mcrypt to OpenSSL. Before that, mcrypt chose
AES-256 from the 32-byte key, as did the reference libraries of the time. Tatra banka's specification of the AES mode
is not public, so the output is kept as it is until the bank confirms which one it expects; see
[#34](https://github.com/tomaj/omnipay-tatra/issues/34). Prefer an HMAC key.

```bash
printf '%s' "$DATA" | openssl dgst -sha1 -binary | head -c 16 \
  | openssl enc -aes-128-ecb -K "${KEY:0:32}" | xxd -p | head -c 32 | tr a-f A-F
```

### DES

With the 8-character key: `SHA-1($data)` encrypted with DES in ECB mode, the first 16 hex characters in uppercase, in
the `SIGN` field. OpenSSL 3 offers DES only through its legacy provider; without it signing throws
`Omnipay\Core\Exception\SignatureException`.

```bash
printf '%s' "$DATA" | openssl dgst -sha1 -binary \
  | openssl enc -des-ecb -provider legacy -provider default -K "$(printf '%s' "$KEY" | xxd -p)" | xxd -p | head -c 16 | tr a-f A-F
```

## Signed fields

`CURR` is the numeric currency code (`978` for EUR).

| Message | HMAC | AES / DES |
|---|---|---|
| TatraPay `purchase()` | `MID AMT CURR VS SS CS RURL REM TIMESTAMP` | `MID AMT CURR VS SS CS RURL` |
| TatraPay return | `AMT CURR VS SS CS RES TID TIMESTAMP` | `VS SS RES` |
| CardPay `purchase()` | `MID AMT CURR VS RURL IPC NAME REM TIMESTAMP` | `MID AMT CURR VS CS RURL IPC NAME` |
| CardPay return | `AMT CURR VS RES AC TRES CC RC TID TIMESTAMP` | `VS RES AC` |
| CardPay `authorize()` | `MID AMT CURR VS E2E TXN RURL IPC NAME REM TPAY CID ECID TDS_CARDHOLDER TDS_EMAIL TDS_MOBILE_PHONE TDS_BILL_CITY TDS_BILL_COUNTRY TDS_BILL_ADDRESS1 TDS_BILL_ADDRESS2 TDS_BILL_ZIP TDS_SHIP_CITY TDS_SHIP_COUNTRY TDS_SHIP_ADDRESS1 TDS_SHIP_ADDRESS2 TDS_SHIP_ZIP TDS_ADDR_MATCH TIMESTAMP` | - |
| CardPay `completeAuthorize()` | `AMT CURR VS TXN RES AC TRES CID CC RC TID TIMESTAMP` | - |
| CardPay `cancelAuthorize()` request | `MID AMT TID VS TXN REM TIMESTAMP` | - |
| CardPay `cancelAuthorize()` response | the XML body, `HMAC=` in the `Authorization` header | - |
| ComfortPay `purchase()` | `MID AMT CURR VS RURL IPC NAME REM TPAY TIMESTAMP` | `MID AMT CURR VS CS RURL IPC NAME TPAY` |
| ComfortPay return | `AMT CURR VS RES AC TRES CID CC RC TID TIMESTAMP` | `VS TRES AC CID` |

On the return, the library signs the **expected** `VS` (and `SS`, `CS`, `AMT`, `CURR`) you pass to the request, not the
values in the query string, and rejects a callback whose `VS` differs. The other fields come from the query string.

## What you still check

- The amount, for AES and DES keys: their return signatures do not cover it.
- That an order is paid once: a customer can open the return URL again.
- The ComfortPay web service is authenticated by TLS with your client certificate, not by these signatures.
