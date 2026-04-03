#!/bin/bash
# ============================================================
# read_debug.sh — Read the latest Behat debug dump
#
# Usage:
#   ./read_debug.sh              → shows the latest meta + form_summary
#   ./read_debug.sh full         → shows the latest full HTML
#   ./read_debug.sh all          → shows meta + form_summary + full HTML
#   ./read_debug.sh clean        → delete all debug files
# ============================================================

DEBUG_DIR="$(dirname "$0")/behat_debug"

if [ ! -d "$DEBUG_DIR" ]; then
    echo "No behat_debug/ directory found. No failures captured yet."
    exit 0
fi

case "${1:-summary}" in
    clean)
        rm -f "$DEBUG_DIR"/*.html "$DEBUG_DIR"/*.txt
        echo "Debug files cleaned."
        exit 0
        ;;
    full)
        LATEST=$(ls -t "$DEBUG_DIR"/*_full.html 2>/dev/null | head -1)
        if [ -z "$LATEST" ]; then
            echo "No full HTML dump found."
            exit 0
        fi
        echo "=== LATEST FULL HTML DUMP: $(basename "$LATEST") ==="
        cat "$LATEST"
        ;;
    all)
        # Meta
        LATEST_META=$(ls -t "$DEBUG_DIR"/*_meta.txt 2>/dev/null | head -1)
        if [ -n "$LATEST_META" ]; then
            echo ""
            echo "=== META ==="
            cat "$LATEST_META"
        fi
        echo ""
        # Form summary
        LATEST_SUMMARY=$(ls -t "$DEBUG_DIR"/*_form_summary.txt 2>/dev/null | head -1)
        if [ -n "$LATEST_SUMMARY" ]; then
            echo ""
            echo "=== FORM SUMMARY ==="
            cat "$LATEST_SUMMARY"
        fi
        echo ""
        # Full HTML (first 500 lines to avoid flooding)
        LATEST_FULL=$(ls -t "$DEBUG_DIR"/*_full.html 2>/dev/null | head -1)
        if [ -n "$LATEST_FULL" ]; then
            echo ""
            echo "=== FULL HTML (first 500 lines) ==="
            head -500 "$LATEST_FULL"
        fi
        ;;
    summary|*)
        # Meta
        LATEST_META=$(ls -t "$DEBUG_DIR"/*_meta.txt 2>/dev/null | head -1)
        if [ -n "$LATEST_META" ]; then
            echo "=== META ==="
            cat "$LATEST_META"
            echo ""
        fi
        # Form summary
        LATEST_SUMMARY=$(ls -t "$DEBUG_DIR"/*_form_summary.txt 2>/dev/null | head -1)
        if [ -n "$LATEST_SUMMARY" ]; then
            echo "=== FORM SUMMARY ==="
            cat "$LATEST_SUMMARY"
        else
            echo "No form summary found."
        fi
        ;;
esac

