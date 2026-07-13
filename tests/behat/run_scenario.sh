#!/bin/bash
# =============================================================================
# run_scenario.sh — Shorthand wrapper around run_and_log.sh
#
# Convenience script: pass a line number and a feature name (without extension)
# instead of typing the full path each time.  Delegates all real work to
# run_and_log.sh, which produces behat_last_run.log and behat_last_errors.log.
#
# Usage:
#   ./run_scenario.sh <line> [FeatureName]
#   ./run_scenario.sh 0      [FeatureName]   # 0 = run ALL scenarios in the file
#
# Arguments:
#   $1  line number of the scenario to run (use 0 to run the whole file)
#   $2  feature file name WITHOUT .feature extension (default: SpecialCase1)
#
# Examples:
#   ./run_scenario.sh 42 SpecialCase2        # runs SpecialCase2.feature:42
#   ./run_scenario.sh 0  SpecialCase1optim   # runs all of SpecialCase1optim.feature
#   ./run_scenario.sh 0                      # runs all of SpecialCase1.feature
# =============================================================================
set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
LINE="${1:-0}"
FEATURE="${2:-SpecialCase1}"

# The `2>/dev/null` suppresses the arithmetic error when $LINE is non-numeric
# (e.g. if someone passes "all" by mistake) — it falls through to the else branch.
if [ "$LINE" -eq 0 ] 2>/dev/null || [ "$LINE" = "0" ]; then
    echo "=== Running ALL scenarios in ${FEATURE}.feature ==="
    echo ""
    bash "$SCRIPT_DIR/run_and_log.sh" "features/${FEATURE}.feature"
else
    echo "=== Running ${FEATURE}.feature:${LINE} ==="
    echo ""
    bash "$SCRIPT_DIR/run_and_log.sh" "features/${FEATURE}.feature:${LINE}"
fi
