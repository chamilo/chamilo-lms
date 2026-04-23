#!/bin/bash
# ============================================================
# run_and_debug.sh — Run a Behat scenario AND show debug info
#
# Usage:
#   ./run_and_debug.sh <feature_file> [line_number]
#
# Examples:
#   ./run_and_debug.sh features/SpecialCase1.feature 10
#   ./run_and_debug.sh features/debugTest.feature
#
# After execution:
#   - If the test PASSES: shows "ALL PASSED"
#   - If the test FAILS: shows meta + form_summary automatically
# ============================================================

BEHAT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(cd "$BEHAT_DIR/../.." && pwd)"
DEBUG_DIR="$BEHAT_DIR/behat_debug"
OUTPUT_FILE="/mnt/c/wamp64/www/chamilo2/tests/behat/wsl_output.txt"

FEATURE_FILE="$1"
LINE="$2"

if [ -z "$FEATURE_FILE" ]; then
    echo "Usage: $0 <feature_file> [line_number]"
    echo "Example: $0 features/SpecialCase1.feature 10"
    exit 1
fi

# Clean previous debug files
rm -f "$DEBUG_DIR"/*_full.html "$DEBUG_DIR"/*_form_summary.txt "$DEBUG_DIR"/*_meta.txt 2>/dev/null

# Build the behat command
if [ -n "$LINE" ]; then
    BEHAT_TARGET="${BEHAT_DIR}/${FEATURE_FILE}:${LINE}"
else
    BEHAT_TARGET="${BEHAT_DIR}/${FEATURE_FILE}"
fi

echo "=================================================="
echo "Running: vendor/bin/behat $BEHAT_TARGET"
echo "=================================================="

cd "$PROJECT_DIR"
vendor/bin/behat "$BEHAT_TARGET" --config "${BEHAT_DIR}/behat.yml" 2>&1
BEHAT_EXIT=$?

echo ""
echo "=================================================="

if [ $BEHAT_EXIT -eq 0 ]; then
    echo "ALL PASSED"
    echo "=================================================="
    # Write to output file for Windows reading
    echo "ALL PASSED" > "$OUTPUT_FILE"
else
    echo "FAILED — Reading debug dumps..."
    echo "=================================================="
    echo ""

    # Collect all output into the Windows-readable file
    > "$OUTPUT_FILE"

    # Find the latest meta file
    LATEST_META=$(ls -t "$DEBUG_DIR"/*_meta.txt 2>/dev/null | head -1)
    if [ -n "$LATEST_META" ]; then
        echo "=== META ===" | tee -a "$OUTPUT_FILE"
        cat "$LATEST_META" | tee -a "$OUTPUT_FILE"
        echo "" | tee -a "$OUTPUT_FILE"
    fi

    # Find the latest form summary
    LATEST_SUMMARY=$(ls -t "$DEBUG_DIR"/*_form_summary.txt 2>/dev/null | head -1)
    if [ -n "$LATEST_SUMMARY" ]; then
        echo "=== FORM SUMMARY ===" | tee -a "$OUTPUT_FILE"
        cat "$LATEST_SUMMARY" | tee -a "$OUTPUT_FILE"
        echo "" | tee -a "$OUTPUT_FILE"
    fi

    # Also add first 200 lines of full HTML for deep analysis
    LATEST_FULL=$(ls -t "$DEBUG_DIR"/*_full.html 2>/dev/null | head -1)
    if [ -n "$LATEST_FULL" ]; then
        echo "=== FULL HTML (first 200 lines) ===" >> "$OUTPUT_FILE"
        head -200 "$LATEST_FULL" >> "$OUTPUT_FILE"
        echo "" >> "$OUTPUT_FILE"
        echo "(Full HTML available in: $LATEST_FULL)" | tee -a "$OUTPUT_FILE"
    fi

    echo ""
    echo "Debug output also written to: $OUTPUT_FILE"
    echo "(readable from Windows at C:\\wamp64\\www\\chamilo2\\tests\\behat\\wsl_output.txt)"
fi

exit $BEHAT_EXIT

