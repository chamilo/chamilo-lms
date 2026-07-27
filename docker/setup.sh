#!/usr/bin/env bash
# Bring up the Chamilo stack and install the portal.
#
#   docker/setup.sh              full setup (idempotent-ish; skips install if done)
#   docker/setup.sh --fresh      wipe the database + .env and reinstall from zero
#   docker/setup.sh --assets     also yarn install + encore production
#
# Mirrors the sequence in .github/workflows/{behat,phpunit}.yml.
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

FRESH=0
ASSETS=0
for arg in "$@"; do
    case "$arg" in
        --fresh)  FRESH=1 ;;
        --assets) ASSETS=1 ;;
        -h|--help) sed -n '2,9p' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
        *) echo "unknown option: $arg" >&2; exit 2 ;;
    esac
done

. docker/lib.sh

docker info >/dev/null 2>&1 || die "Docker is not running. Start Docker Desktop and retry."

# ---------------------------------------------------------------------------
# .env.local and .env.test.local are gitignored (they are per-machine config) but
# the stack cannot work without them: the committed .env.test points PHPUnit at
# 127.0.0.1 with root/root, which is correct for CI and wrong here. So a fresh
# clone has to have them created, or the test suite fails with connection errors
# and the app boots in prod mode. Templates live in docker/env/.
# Existing files are never overwritten — local edits are respected.
for pair in ".env.local:docker/env/env.local.dist" \
            ".env.test.local:docker/env/env.test.local.dist"; do
    target="${pair%%:*}"
    template="${pair#*:}"
    if [[ ! -f "$target" ]]; then
        cp "$template" "$target"
        info "created ${target} from ${template}"
    fi
done

# ---------------------------------------------------------------------------
bold "Starting containers"
"${DC[@]}" up -d --build web db redis mailpit
"${DC[@]}" --profile behat up -d selenium

# ---------------------------------------------------------------------------
bold "Waiting for Apache on ${BASE_URL}"
for i in $(seq 1 60); do
    if curl -fsS -o /dev/null -w '%{http_code}' "${BASE_URL}/main/install/index.php" \
        | grep -qE '^(200|30[0-9])$'; then
        info "Apache is serving"
        break
    fi
    [[ "$i" == 60 ]] && die "Apache did not respond in 60s. Try: docker compose logs web"
    sleep 1
done

bold "Waiting for Selenium grid"
wait_for_selenium
info "Selenium grid is ready"

# ---------------------------------------------------------------------------
if [[ "$FRESH" == 1 ]]; then
    bold "Wiping database and .env (--fresh)"
    "${DC[@]}" exec -T db mariadb -uroot -proot -e \
        "DROP DATABASE IF EXISTS chamilo;
         CREATE DATABASE chamilo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
         GRANT ALL PRIVILEGES ON chamilo.* TO 'chamilo'@'%';
         FLUSH PRIVILEGES;"
    # Removed, not emptied: Dotenv falls back to .env.dist only when .env is
    # absent, and an empty .env would shadow every default in it.
    rm -f .env
    "${EXEC[@]}" rm -rf var/cache/dev var/cache/prod var/cache/test
    info "database chamilo recreated, .env removed"
fi

# ---------------------------------------------------------------------------
# Vendor deps. Reused from the host bind mount when already present — vendor/ is
# pure PHP so it is platform-independent.
if [[ ! -f vendor/autoload_runtime.php ]]; then
    bold "Installing PHP dependencies"
    "${EXEC[@]}" composer install --no-progress --no-interaction
else
    info "vendor/ already present — skipping composer install"
fi

bold "Installing bundle assets"
"${EXEC[@]}" php bin/console assets:install public --no-interaction

# public/build/ is gitignored, so a fresh clone has no compiled frontend and the
# portal would render without CSS/JS. Build it automatically in that case rather
# than making a first-time user discover the --assets flag from an error message.
if [[ "$ASSETS" == 1 || ! -f public/build/entrypoints.json ]]; then
    if [[ "$ASSETS" != 1 ]]; then
        info "public/build/ is missing — building the frontend (normal on a fresh clone)"
    fi
    bold "Building frontend (yarn + encore) — this takes a while"
    # node_modules is a named volume, so this builds Linux binaries that will not
    # fight a host-side yarn install done for macOS/Windows.
    "${EXEC[@]}" yarn install
    "${EXEC[@]}" yarn run encore production
else
    info "public/build/ already compiled — skipping encore (use --assets to rebuild)"
fi

# ---------------------------------------------------------------------------
# Install the portal. There is no CLI installer in Chamilo 2.0; the wizard at
# /main/install/index.php is the only supported path, so we drive it with the
# project's own Behat feature.
if grep -q "APP_INSTALLED='1'" .env 2>/dev/null; then
    info "Portal already installed (.env has APP_INSTALLED='1'); use --fresh to reinstall"
else
    bold "Installing the portal via the install wizard"
    cp docker/behat/installDocker.feature tests/behat/features/installDocker.feature
    trap 'rm -f tests/behat/features/installDocker.feature' EXIT
    if ! behat_run features/installDocker.feature -vv; then
        rm -f tests/behat/features/installDocker.feature
        die "Install wizard failed. Inspect: docker compose logs web"
    fi
    rm -f tests/behat/features/installDocker.feature
    trap - EXIT
    info "portal installed"
fi

# ---------------------------------------------------------------------------
# The wizard records access_url.url without the port — it comes out as
# "http://localhost/" even though Apache is on ${CHAMILO_PORT}. Chamilo builds
# absolute URLs (assets, mail links, LP and document paths) from this row, so a
# missing port leaves the portal generating links to port 80. Always correct it.
bold "Setting access_url to ${BASE_URL}/"
"${DC[@]}" exec -T db mariadb -uchamilo -pchamilo chamilo \
    -e "UPDATE access_url SET url='${BASE_URL}/' WHERE id=1;"
"${DC[@]}" exec -T db mariadb -uchamilo -pchamilo chamilo \
    -e "SELECT id, url FROM access_url;"

bold "Clearing cache"
"${EXEC[@]}" php bin/console cache:clear --no-interaction
"${EXEC[@]}" chmod -R 0777 var

# ---------------------------------------------------------------------------
bold "Preparing the PHPUnit database (chamilo_test)"
"${EXEC[@]}" php bin/console --env=test doctrine:database:create --if-not-exists
"${EXEC[@]}" php bin/console --env=test doctrine:schema:drop --force --full-database
"${EXEC[@]}" php bin/console --env=test doctrine:schema:create
"${EXEC[@]}" php bin/console --env=test doctrine:fixtures:load --no-interaction

# ---------------------------------------------------------------------------
cat <<EOF

$(printf '\033[1;32m')Stack is up.$(printf '\033[0m')

  Chamilo      ${BASE_URL}          admin / admin
  API docs     ${BASE_URL}/api
  Mailpit      ${MAILPIT_URL}
  Selenium VNC ${SELENIUM_VNC_URL}  (no password — watch Behat run)
  MariaDB      127.0.0.1:3307         chamilo / chamilo

Run tests:
  docker/test.sh phpunit
  docker/test.sh behat features/actionUserLogin.feature
  docker/test.sh phpstan | psalm | ecs

Shell in:
  docker compose exec web bash
EOF
