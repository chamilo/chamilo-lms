#!/bin/bash
# =============================================================================
# watch_and_run.sh — Watch .feature files and re-run Behat on changes to 
# featureContext.php and any feature in the features/ directory.
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

# Feature files now live in subdirectories under features/ (e.g.
# features/SpecialCase/existingPlatform/), so resolve a single-file target to
# its actual path instead of assuming it sits directly under features/.
WATCH_TARGET_PATH=""
if [ -n "$WATCH_TARGET" ]; then
    MATCH=$(find "$FEATURES_DIR" -type f -name "$WATCH_TARGET" | head -1)
    if [ -z "$MATCH" ]; then
        echo "ERROR: no $WATCH_TARGET found anywhere under features/" >&2
        exit 1
    fi
    WATCH_TARGET_PATH="features/${MATCH#"$FEATURES_DIR"/}"
fi

echo "=== Behat Watch Mode ==="
echo "Watching: ${WATCH_TARGET_PATH:-all .feature files in $FEATURES_DIR (recursive)}"
echo "Press Ctrl+C to stop."
echo ""

while true; do
    if [ -n "$WATCH_TARGET_PATH" ]; then
        WATCH_PATH="$SCRIPT_DIR/$WATCH_TARGET_PATH"
    else
        WATCH_PATH="$FEATURES_DIR"
    fi

    # Block until any modify or create event fires on the watched path.
    # -r watches subdirectories too, since feature files are now nested.
    # -q suppresses the "Watching..." startup line; --format '%w%f' returns
    # the watched directory plus filename so nested changes resolve to the
    # correct relative path (plain '%f' would drop the subdirectory).
    CHANGED=$(inotifywait -q -r -e modify -e create --format '%w%f' "$WATCH_PATH" 2>/dev/null)
    echo ""
    echo ">>> Change detected: $CHANGED at $(date '+%H:%M:%S') — running Behat..."
    echo ""

    if [ -n "$WATCH_TARGET_PATH" ]; then
        bash "$SCRIPT_DIR/run_and_log.sh" "$WATCH_TARGET_PATH"
    else
        bash "$SCRIPT_DIR/run_and_log.sh" "features/${CHANGED#"$FEATURES_DIR"/}"
    fi

    echo ""
    echo ">>> Waiting for next change..."
done
