---
title: Signatures and openssl
description: How omnipay-tatra signs a TatraPay request with an HMAC, AES and DES key, and the same signatures computed independently with the openssl command line.
---

# Signatures and openssl

If a signature is rejected, you want to see the string that was signed and compute the signature yourself. This
example builds a TatraPay request with each key type, puts the signed string together from the fields of the redirect,
as the bank does, and compares the library's signature with one computed by the `openssl` command line. It runs
offline.

Source: [`examples/signatures/run.php`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/signatures/run.php) ·
run it with `examples/ci/example.sh signatures`.

## The keys

<<< @/../examples/signatures/run.php#keys

The length of the key selects the algorithm: 128 hexadecimal characters HMAC-SHA256, 64 AES, 8 DES. These keys are made
up for the example; any other length throws `UnsupportedKeyException`.

## The request

<<< @/../examples/signatures/run.php#request

`purchase()` sets `TIMESTAMP` to the current UTC time; the example fixes it so that the HMAC is the same on every run.

## The signed string

<<< @/../examples/signatures/run.php#signed

The fields are concatenated without separators, and an empty optional field adds nothing. HMAC requests also sign
`REM` and `TIMESTAMP`. The field lists of every request and return are on the [Signatures](/guide/signatures#signed-fields)
page.

## The same signature with openssl

<<< @/../examples/signatures/run.php#openssl

- **HMAC**: HMAC-SHA256 with the key as hex bytes, lowercase hex.
- **AES**: the first 16 bytes of SHA-1, encrypted with AES-128-ECB and the **first 32 hex characters** of the key,
  uppercase hex. See the note on [AES-128 vs AES-256](/guide/signatures#aes).
- **DES**: SHA-1 encrypted with DES-ECB, the first 8 bytes, uppercase hex. OpenSSL 3 needs its legacy provider for
  DES, both for PHP (`OPENSSL_CONF` pointing at
  [`openssl-legacy.cnf`](https://github.com/tomaj/omnipay-tatra/blob/master/examples/signatures/openssl-legacy.cnf))
  and for the command line (`-provider legacy -provider default`).

On macOS, `/usr/bin/openssl` is LibreSSL, which lacks these options: run the example with
`OPENSSL=/opt/homebrew/opt/openssl@3/bin/openssl`.

## Output

<<< @/../examples/signatures/expected-output.txt{text}

## What it tests

- the library and an independent implementation agree on all three algorithms;
- the field order of the TatraPay request, with and without `REM` and `TIMESTAMP`.
