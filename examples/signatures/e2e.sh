#!/usr/bin/env bash
# Runs run.php and compares its output with expected-output.txt.
# Runs offline. OPENSSL is the openssl binary (OpenSSL 3, default openssl); DES needs its legacy provider.
set -euo pipefail
cd "$(dirname "$0")"
[[ -f vendor/autoload.php ]] || { echo "fail: vendor/ is missing, run composer install first" >&2; exit 1; }
export OPENSSL_CONF="${OPENSSL_CONF:-$PWD/openssl-legacy.cnf}"
diff -u expected-output.txt <(php run.php)
echo "ok: signatures"
