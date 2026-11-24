#!/usr/bin/env bash
# Runs run.php and compares its output with expected-output.txt.
# Needs payment-simulator at SIMULATOR_URL (default http://127.0.0.1:8080), see ../ci/start-simulator.sh.
set -euo pipefail
cd "$(dirname "$0")"
[[ -f vendor/autoload.php ]] || { echo "fail: vendor/ is missing, run composer install first" >&2; exit 1; }
diff -u expected-output.txt <(php -d display_errors=stderr run.php)
echo "ok: tatrapay"
