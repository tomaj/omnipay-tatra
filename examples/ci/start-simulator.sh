#!/usr/bin/env bash
# Starts payment-simulator (https://github.com/tomaj/payment-simulator) on the PHP built-in server at SIMULATOR_URL
# (default http://127.0.0.1:8080), unless something already answers there. Clones it into PAYMENT_SIMULATOR_DIR
# (default: a temporary directory) when that directory does not exist yet. The server keeps running after the script.
set -euo pipefail
url="${SIMULATOR_URL:-http://127.0.0.1:8080}"
if curl -sf -o /dev/null "$url/"; then
    echo "payment-simulator is already running at $url"
    exit 0
fi

dir="${PAYMENT_SIMULATOR_DIR:-${RUNNER_TEMP:-${TMPDIR:-/tmp}}/payment-simulator}"
if [[ ! -d "$dir" ]]; then
    git clone --quiet --depth 1 https://github.com/tomaj/payment-simulator.git "$dir"
fi
[[ -f "$dir/vendor/autoload.php" ]] || composer install --working-dir="$dir" --no-dev --no-progress --no-interaction --quiet
mkdir -p "$dir/temp" "$dir/log"

# the DES endpoints need the OpenSSL 3 legacy provider
OPENSSL_CONF="$dir/docker/php/openssl-legacy.cnf" nohup php -S "${url#http://}" -t "$dir/www" "$dir/www/index.php" \
    > "$dir/log/php-server.log" 2>&1 &
for _ in $(seq 1 50); do
    if curl -sf -o /dev/null "$url/"; then
        echo "payment-simulator from $dir is running at $url"
        exit 0
    fi
    sleep 0.2
done
echo "fail: payment-simulator did not start at $url" >&2
cat "$dir/log/php-server.log" >&2
exit 1
