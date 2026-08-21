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

# Feature files now live in subdirectories under features/ (e.g.
# features/SpecialCase/existingPlatform/), so resolve the name to its actual
# path instead of assuming it sits directly under features/.
MATCH=$(find "$SCRIPT_DIR/features" -type f -name "${FEATURE}.feature" | head -1)
if [ -z "$MATCH" ]; then
    echo "ERROR: no ${FEATURE}.feature found anywhere under features/" >&2
    exit 1
fi
FEATURE_PATH="features/${MATCH#"$SCRIPT_DIR"/features/}"

# existingPlatform/ and SpecialCase1optim are tagged @internal and excluded
# from the default suite. Point at behat.internal.yml so an intentional run
# of those files is not filtered out.
if [[ "$FEATURE_PATH" == features/SpecialCase/existingPlatform/* ]] \
    || [[ "$FEATURE_PATH" == features/SpecialCase/newPlatform/SpecialCase1optim.feature ]]; then
    export BEHAT_CONFIG=behat.internal.yml
fi

# The `2>/dev/null` suppresses the arithmetic error when $LINE is non-numeric
# (e.g. if someone passes "all" by mistake) — it falls through to the else branch.
if [ "$LINE" -eq 0 ] 2>/dev/null || [ "$LINE" = "0" ]; then
    echo "=== Running ALL scenarios in ${FEATURE_PATH} ==="
    echo ""
    bash "$SCRIPT_DIR/run_and_log.sh" "$FEATURE_PATH"
else
    echo "=== Running ${FEATURE_PATH}:${LINE} ==="
    echo ""
    bash "$SCRIPT_DIR/run_and_log.sh" "${FEATURE_PATH}:${LINE}"
fi
