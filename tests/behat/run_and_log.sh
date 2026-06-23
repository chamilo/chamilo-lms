#!/bin/bash
# =============================================================================
# run_and_log.sh — Main Behat test runner with full logging
#
# Runs Behat with any arguments forwarded from the caller, then writes output
# to two log files that are overwritten on every run:
#   behat_last_run.log   — complete output of the run (all steps, all colors)
#   behat_last_errors.log — extracted failures/errors only, for quick review
#
# Usage (direct):
#   ./run_and_log.sh features/SpecialCase1.feature
#   ./run_and_log.sh features/SpecialCase1.feature:42   # single scenario by line
#   ./run_and_log.sh                                    # run the full default suite
#
# Typically called indirectly via:
#   run_scenario.sh   — shorthand wrapper (line number + feature name)
#   watch_and_run.sh  — auto-re-run on .feature file change
#
# WSL note: run this script from inside WSL (/var/www/chamilo-lms), not from
# Windows directly — ChromeDriver and PHP must run in the Linux environment.
# =============================================================================
set -euo pipefail

# Launch Behat and log output
# Helps identify changes upstream by comparing logs

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

# Temporarily disable errexit so a Behat failure (non-zero exit) does not kill
# this script before we can capture the exit code and extract error details.
set +e
php "$PROJECT_ROOT/vendor/bin/behat" \
    --config behat.yml \
    --format pretty \
    --no-interaction \
    --stop-on-failure \
    --colors \
    "$@" 2>&1 | tee -a "$FULL_LOG"
# PIPESTATUS[0] captures Behat's exit code, not tee's (which is always 0).
EXIT_CODE=${PIPESTATUS[0]}
set -e

echo "" | tee -a "$FULL_LOG"
echo "========================================" | tee -a "$FULL_LOG"
echo "Behat run finished at $(date '+%Y-%m-%d %H:%M:%S') -- exit code: $EXIT_CODE" | tee -a "$FULL_LOG"
echo "========================================" | tee -a "$FULL_LOG"

# Post-process the full log into a compact error summary.
# The grep extracts the 3 lines before/after each failure keyword so you can
# see the failing step in context without reading thousands of lines.
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
