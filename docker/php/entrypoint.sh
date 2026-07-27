#!/bin/bash
# Chamilo container entrypoint: make the bind-mounted tree writable, wait for
# MariaDB, then hand off to whatever CMD was given (apache2-foreground by default).
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/chamilo}"
cd "$APP_DIR"

log() { printf '\033[36m[entrypoint]\033[0m %s\n' "$*"; }

# ---------------------------------------------------------------------------
# Writable paths. install.lib.php checks var/ and config/ with is_writable(),
# and the wizard writes .env into the project root.
# Bind mounts from macOS do not carry usable uids, so we go wide like CI does.
# ---------------------------------------------------------------------------
mkdir -p \
    var/cache var/log var/upload var/courses var/themes var/translations \
    config/jwt

chmod -R 0777 var config 2>/dev/null || log "warn: could not chmod parts of var/ config/"

# Do NOT create an empty .env here. Symfony's Dotenv falls back to .env.dist
# when .env is absent, and that fallback is what supplies TRUSTED_PROXIES,
# APP_ENCRYPT_METHOD, SOFTWARE_NAME and friends before the wizard has run.
# An empty .env shadows the fallback and every console command dies with
# "Environment variable not found". The wizard creates the real .env itself.
[[ -f .env ]] && chmod 0666 .env 2>/dev/null || true

# ---------------------------------------------------------------------------
# JWT keypair for lexik/jwt-authentication-bundle (API Platform auth).
# config/jwt/*.pem is gitignored, so it will not exist on a fresh clone.
# ---------------------------------------------------------------------------
JWT_PASS="${JWT_PASSPHRASE:-your_secret_passphrase}"
if [[ ! -f config/jwt/private.pem ]]; then
    log "generating JWT keypair"
    openssl genpkey -out config/jwt/private.pem \
        -aes-256-cbc -pass "pass:${JWT_PASS}" \
        -algorithm rsa -pkeyopt rsa_keygen_bits:4096 2>/dev/null
    openssl pkey -in config/jwt/private.pem -passin "pass:${JWT_PASS}" \
        -out config/jwt/public.pem -pubout 2>/dev/null
    chmod 0644 config/jwt/*.pem
fi

# ---------------------------------------------------------------------------
# Wait for MariaDB. Chamilo's installer fails opaquely if the DB is not up.
#
# These use WAIT_DB_* rather than DATABASE_* on purpose: real environment
# variables take precedence over .env files in Symfony's Dotenv, so exporting
# DATABASE_NAME here would override .env.test's chamilo_test and point the
# PHPUnit suite at the live database.
# ---------------------------------------------------------------------------
if [[ -n "${WAIT_DB_HOST:-}" ]]; then
    log "waiting for database at ${WAIT_DB_HOST}:${WAIT_DB_PORT:-3306}"
    for i in $(seq 1 60); do
        if mysqladmin ping \
            --host="${WAIT_DB_HOST}" \
            --port="${WAIT_DB_PORT:-3306}" \
            --user="${WAIT_DB_USER:-root}" \
            --password="${WAIT_DB_PASSWORD:-}" \
            --silent >/dev/null 2>&1; then
            log "database is up"
            break
        fi
        if [[ "$i" == 60 ]]; then
            log "ERROR: database did not become ready in 60s"
            exit 1
        fi
        sleep 1
    done
fi

log "ready — $*"
exec "$@"
