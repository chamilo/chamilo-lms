#!/bin/bash
# =============================================================================
# watch_and_run.sh — Watch .feature files and re-run Behat on change.
# Requires: inotifywait (sudo apt install inotify-tools)
#
# Usage:
#   ./watch_and_run.sh                                  # watch all features
#   ./watch_and_run.sh SpecialCase1.feature             # watch one file only
# =============================================================================

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
FEATURES_DIR="$SCRIPT_DIR/features"
WATCH_TARGET="${1:-}"

if ! command -v inotifywait &>/dev/null; then
    echo "ERROR: inotifywait not found. Install it with:  sudo apt install inotify-tools"
    exit 1
fi

echo "=== Behat Watch Mode ==="
echo "Watching: ${WATCH_TARGET:-all .feature files in $FEATURES_DIR}"
echo "Press Ctrl+C to stop."
echo ""

while true; do
    if [ -n "$WATCH_TARGET" ]; then
        WATCH_PATH="$FEATURES_DIR/$WATCH_TARGET"
    else
        WATCH_PATH="$FEATURES_DIR"
    fi

    # Wait for a file change
    CHANGED=$(inotifywait -q -e modify -e create --format '%f' "$WATCH_PATH" 2>/dev/null)
    echo ""
    echo ">>> Change detected: $CHANGED at $(date '+%H:%M:%S') — running Behat..."
    echo ""

    if [ -n "$WATCH_TARGET" ]; then
        bash "$SCRIPT_DIR/run_and_log.sh" "features/$WATCH_TARGET"
    else
        bash "$SCRIPT_DIR/run_and_log.sh" "features/$CHANGED"
    fi

    echo ""
    echo ">>> Waiting for next change..."
done
