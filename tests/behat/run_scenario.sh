#!/bin/bash
set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
LINE="${1:-0}"
FEATURE="${2:-SpecialCase1}"

if [ "$LINE" -eq 0 ] 2>/dev/null || [ "$LINE" = "0" ]; then
    echo "=== Running ALL scenarios in ${FEATURE}.feature ==="
    echo ""
    bash "$SCRIPT_DIR/run_and_log.sh" "features/${FEATURE}.feature"
else
    echo "=== Running ${FEATURE}.feature:${LINE} ==="
    echo ""
    bash "$SCRIPT_DIR/run_and_log.sh" "features/${FEATURE}.feature:${LINE}"
fi
