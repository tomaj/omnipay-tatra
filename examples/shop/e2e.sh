#!/usr/bin/env bash
# Starts the shop on the PHP built-in server, plays the customer and compares the output with expected-output.txt.
# Needs payment-simulator at SIMULATOR_URL (default http://127.0.0.1:8080), see ../ci/start-simulator.sh.
set -euo pipefail
cd "$(dirname "$0")"
[[ -f vendor/autoload.php ]] || { echo "fail: vendor/ is missing, run composer install first" >&2; exit 1; }

export SHOP_URL="${SHOP_URL:-http://127.0.0.1:8081}"
mkdir -p var
rm -f var/orders.json
log="$(mktemp)"
php -d display_errors=0 -d log_errors=1 -S "${SHOP_URL#http://}" -t public public/index.php > "$log" 2>&1 &
SHOP=$!
trap 'kill $SHOP 2>/dev/null; wait $SHOP 2>/dev/null || true; rm -f "$log"' EXIT
for _ in $(seq 1 50); do curl -s -o /dev/null "$SHOP_URL/orders/0" && break; sleep 0.1; done

if ! diff -u expected-output.txt <(php -d display_errors=stderr customer.php); then
    echo "fail: the output differs from expected-output.txt; the shop's log:" >&2
    cat "$log" >&2
    exit 1
fi
echo "ok: shop"
