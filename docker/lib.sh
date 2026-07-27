# Shared settings for docker/setup.sh and docker/test.sh. Source, don't run.

# Must match the published host port in docker-compose.yml AND the HTTP_PORT
# build arg in docker/php/Dockerfile — all three are the same number by design.
CHAMILO_PORT=8380
BASE_URL="http://localhost:${CHAMILO_PORT}"

MAILPIT_URL='http://localhost:8325'
SELENIUM_URL='http://localhost:4444'
SELENIUM_VNC_URL='http://localhost:7900'

# tests/behat/behat.yml hardcodes base_url http://localhost (port 80) for CI,
# where Apache serves on 80. Here it serves on CHAMILO_PORT, so Behat needs a
# different base_url or Chrome gets ERR_CONNECTION_REFUSED.
#
# This is a separate config file rather than a BEHAT_PARAMS override because
# Behat 3.29 merges the file config AFTER the env-var config and later wins —
# so behat.yml's base_url silently beat BEHAT_PARAMS. See the header comment in
# tests/behat/behat.docker.yml.
BEHAT_CONFIG='behat.docker.yml'

DC=(docker compose)
EXEC=("${DC[@]}" exec -T web)

bold() { printf '\n\033[1;36m==> %s\033[0m\n' "$*"; }
info() { printf '    %s\n' "$*"; }
die()  { printf '\n\033[1;31mERROR:\033[0m %s\n' "$*" >&2; exit 1; }

# Hard ceiling per Behat invocation. Without it, a grid that dies mid-feature
# leaves Behat blocked on a dead session forever (observed: the node was purged
# with "DOWN for too long" and the run never returned). `timeout` comes from
# coreutils in the container, so this works regardless of the host OS.
#
# 40 minutes is generous on purpose: a scenario costs roughly 50s through a real
# browser, and course.feature alone has 30 of them (~25 min). A tighter cap
# silently truncates the feature — it gets killed mid-scenario and prints no
# summary at all, which reads like a crash. Override with BEHAT_TIMEOUT=<secs>.
BEHAT_TIMEOUT="${BEHAT_TIMEOUT:-2400}"

# Run behat inside the web container against the Docker profile.
behat_run() {
    "${DC[@]}" exec -T -w /var/www/chamilo/tests/behat web \
        timeout --preserve-status --kill-after=30s "${BEHAT_TIMEOUT}" \
        ../../vendor/behat/behat/bin/behat --config "${BEHAT_CONFIG}" "$@"
}

wait_for_selenium() {
    for i in $(seq 1 90); do
        if curl -fsS "${SELENIUM_URL}/status" 2>/dev/null | grep -q '"ready": *true'; then
            return 0
        fi
        sleep 2
    done
    die "Selenium not ready — try: docker compose logs selenium"
}

# The standalone grid degrades over a long run: its node misses heartbeats, gets
# marked DOWN and is eventually purged, after which every session request fails.
# Recycling the container between features is far cheaper than debugging a
# half-dead grid, and matches CI, which gets a fresh Selenium per job.
reset_selenium() {
    "${DC[@]}" --profile behat up -d selenium >/dev/null 2>&1
    "${DC[@]}" restart selenium >/dev/null 2>&1
    wait_for_selenium
}
