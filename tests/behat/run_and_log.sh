#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
LOG_DIR="$SCRIPT_DIR"
FULL_LOG="$LOG_DIR/behat_last_run.log"
ERROR_LOG="$LOG_DIR/behat_last_errors.log"

echo "========================================" | tee "$FULL_LOG"
echo "Behat run started at $(date '+%Y-%m-%d %H:%M:%S')" | tee -a "$FULL_LOG"
echo "Arguments: $*" | tee -a "$FULL_LOG"
echo "========================================" | tee -a "$FULL_LOG"

cd "$SCRIPT_DIR"

set +e
php "$PROJECT_ROOT/vendor/bin/behat" \
    --config behat.yml \
    --format pretty \
    --no-interaction \
    --colors \
    "$@" 2>&1 | tee -a "$FULL_LOG"
EXIT_CODE=${PIPESTATUS[0]}
set -e

echo "" | tee -a "$FULL_LOG"
echo "========================================" | tee -a "$FULL_LOG"
echo "Behat run finished at $(date '+%Y-%m-%d %H:%M:%S') -- exit code: $EXIT_CODE" | tee -a "$FULL_LOG"
echo "========================================" | tee -a "$FULL_LOG"

echo "Extracting errors into $ERROR_LOG ..."
{
    echo "=== BEHAT ERROR SUMMARY ==="
    echo "Command: behat $*"
    echo "Exit code: $EXIT_CODE"
    echo ""

    if [ "$EXIT_CODE" -eq 0 ]; then
        echo "All scenarios passed. No errors."
    else
        echo "--- Failed / errored steps ---"
        grep -n -B 3 -A 3 -iE '(failed|error|exception|timed out|not found|skipped)' "$FULL_LOG" 2>/dev/null || echo "(no matching lines found)"
        echo ""
        echo "--- Summary lines ---"
        grep -E '(scenario|step|failed|passed|pending|undefined)' "$FULL_LOG" | tail -20
    fi
} > "$ERROR_LOG"

echo ""
echo "Done."
echo "  Full log:  $FULL_LOG"
echo "  Error log: $ERROR_LOG"
echo ""

if [ "$EXIT_CODE" -ne 0 ]; then
    echo ">>> THERE WERE FAILURES. <<<"
    echo ""
    cat "$ERROR_LOG"
fi

exit $EXIT_CODE
