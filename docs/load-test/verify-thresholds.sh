#!/usr/bin/env bash
#
# verify-thresholds.sh — parse k6's summary-export JSON and fail the CI job
# if any launch threshold is breached.
#
# k6's --summary-export writes a rich JSON; we only care about a few keys.
# We don't depend on `jq` being present on weird runners — use Python 3.
#
# Thresholds (mirror the ones declared in workforce-smoke.js):
#   http_req_failed rate  < 0.01
#   http_req_duration p95 < 800ms
#   ai_ask p95           < 3000ms
#   marketing_landing p95 < 400ms

set -euo pipefail

SUMMARY="${1:-load-test-summary.json}"

if [[ ! -f "$SUMMARY" ]]; then
  echo "verify-thresholds: summary file not found at $SUMMARY"
  exit 2
fi

python3 - "$SUMMARY" <<'PY'
import json, sys

path = sys.argv[1]
with open(path) as f:
    data = json.load(f)

metrics = data.get("metrics", {})
failed = False

def check(name, key, subkey, limit, unit):
    global failed
    m = metrics.get(name, {})
    values = m.get("values", m)
    actual = values.get(subkey)
    if actual is None:
        print(f"  {name}.{subkey}: MISSING — cannot verify")
        failed = True
        return
    ok = actual < limit
    symbol = "✓" if ok else "✗"
    print(f"  {symbol} {name}.{subkey} = {actual:.2f}{unit}  (limit {limit}{unit})")
    if not ok:
        failed = True

print("Threshold verification:")
check("http_req_failed",   "http_req_failed",   "rate",   0.01, "")
check("http_req_duration", "http_req_duration", "p(95)",  800,  "ms")

# Tagged sub-metrics land under "http_req_duration{name:...}" keys.
for name in ("ai_ask", "marketing_landing"):
    tagged = f"http_req_duration{{name:{name}}}"
    limit = 3000 if name == "ai_ask" else 400
    check(tagged, tagged, "p(95)", limit, "ms")

sys.exit(1 if failed else 0)
PY
