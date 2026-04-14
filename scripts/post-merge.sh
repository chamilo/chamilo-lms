#!/bin/bash
# Post-merge setup script for Chamilo 2.x on Replit.
# Runs automatically after a task agent merge.
# Must be: idempotent, non-interactive (stdin closed), fast.
set -e

echo "[post-merge] Installing Composer dependencies..."
composer install --no-interaction --prefer-dist 2>&1

echo "[post-merge] Clearing Symfony cache..."
php -d memory_limit=512M bin/console cache:clear --env=dev --no-warmup --no-interaction 2>&1 || true

echo "[post-merge] Done."
