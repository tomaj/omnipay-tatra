#!/usr/bin/env bash
# Runs one example against this checkout of omnipay-tatra: examples/ci/example.sh tatrapay
# Installs the example (its composer.json takes tomaj/omnipay-tatra from the repository root through a path
# repository), starts payment-simulator when the example needs it, and runs the example's e2e.sh.
set -euo pipefail
examples="$(cd "$(dirname "$0")/.." && pwd)"
name="${1:?usage: $0 <example>}"
[[ -x "$examples/$name/e2e.sh" ]] || { echo "fail: $examples/$name/e2e.sh not found" >&2; exit 1; }

composer install --working-dir="$examples/$name" --no-progress --no-interaction
# fail when Composer took omnipay-tatra from anywhere else than this checkout
diff -rq "$examples/../src" "$examples/$name/vendor/tomaj/omnipay-tatra/src" > /dev/null \
    || { echo "fail: vendor/tomaj/omnipay-tatra is not this checkout" >&2; exit 1; }

if [[ "$name" != signatures ]]; then
    "$examples/ci/start-simulator.sh"
fi
"$examples/$name/e2e.sh"
