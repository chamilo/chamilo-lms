#!/usr/bin/env bash
# Change the port Chamilo is served on.
#
#   docker/set-port.sh 9000
#   docker/set-port.sh            # just show the current port
#
# The port lives in four files and they must all agree, because Chamilo stores
# its own web address in the database: the port Apache listens on *inside* the
# container has to be the same one you type in the browser, or every redirect and
# asset URL points somewhere that isn't listening.
#
# After changing it, apply the change with:
#   docker compose down && docker/setup.sh --fresh
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

# file : sed match : sed replacement — %PORT% is substituted with the new port.
# Anchored to the specific line in each file so nothing else can be caught.
TARGETS=(
    "docker/php/Dockerfile|^ARG HTTP_PORT=[0-9]\{1,\}$|ARG HTTP_PORT=%PORT%"
    "docker-compose.yml|^      - \"[0-9]\{1,\}:[0-9]\{1,\}\"   # Chamilo|      - \"%PORT%:%PORT%\"   # Chamilo"
    "docker/lib.sh|^CHAMILO_PORT=[0-9]\{1,\}$|CHAMILO_PORT=%PORT%"
    "tests/behat/behat.docker.yml|^      base_url: http://localhost:[0-9]\{1,\}$|      base_url: http://localhost:%PORT%"
)

current() { sed -n 's/^CHAMILO_PORT=\([0-9]\{1,\}\)$/\1/p' docker/lib.sh; }

if [[ $# -eq 0 ]]; then
    echo "Chamilo is currently served on port $(current)."
    echo "To change it:  docker/set-port.sh <new-port>"
    exit 0
fi

NEW="$1"
[[ "$NEW" =~ ^[0-9]+$ ]] && (( NEW >= 1024 && NEW <= 65535 )) \
    || { echo "ERROR: '$NEW' is not a port between 1024 and 65535." >&2; exit 2; }

OLD="$(current)"
if [[ "$NEW" == "$OLD" ]]; then
    echo "Already on port $NEW — nothing to do."
    exit 0
fi

# Refuse a port something else already holds, rather than failing later at
# `docker compose up` with containers half-created.
#
# Published ports are the numbers immediately before "->" in docker's Ports
# column ("127.0.0.1:8080->8080/tcp"). Matching on ":${NEW}->" instead looks
# right but silently fails when the preceding character is a digit, as in an
# IP-qualified binding — so pull the host port out explicitly.
# Our own containers are skipped: they are about to be recreated anyway.
#
# Reads via process substitution rather than a pipe into `while`: piping into a
# loop and then trimming with `head -1` makes the loop die of SIGPIPE, which
# under `set -e -o pipefail` aborts the whole script before any error can be
# printed — an exit code with no explanation.
port_owner() {
    local want="$1" name ports p found
    while IFS=$'\t' read -r name ports; do
        case "$name" in chamilo-*) continue ;; esac
        found="$(printf '%s' "$ports" | grep -oE '[0-9]+->' | tr -d '>-' || true)"
        for p in $found; do
            if [[ "$p" == "$want" ]]; then
                printf '%s\n' "$name"
                return 0
            fi
        done
    done < <(docker ps --format '{{.Names}}\t{{.Ports}}' 2>/dev/null || true)
    return 0
}

holder="$(port_owner "$NEW")"
if [[ -n "$holder" ]]; then
    echo "ERROR: port ${NEW} is already used by container '${holder}'." >&2
    echo "       Pick a different port, or stop that container first." >&2
    exit 1
fi

# sed -i is not portable (BSD vs GNU take different arguments), so write to a
# temp file and move it into place instead.
for entry in "${TARGETS[@]}"; do
    IFS='|' read -r file match repl <<<"$entry"
    repl="${repl//%PORT%/$NEW}"

    grep -q "$match" "$file" \
        || { echo "ERROR: could not find the port line in ${file}." >&2
             echo "       It may have been edited by hand — fix it there manually." >&2
             exit 1; }

    tmp="$(mktemp)"
    sed "s|${match}|${repl}|" "$file" >"$tmp"
    cat "$tmp" >"$file"          # preserves the original file's permissions
    rm -f "$tmp"
    echo "  updated ${file}"
done

cat <<EOF

Port changed: ${OLD} -> ${NEW}

Apply it with:
  docker compose down
  docker/setup.sh --fresh

Chamilo will then be at http://localhost:${NEW}
(--fresh is required: the old port is recorded in the database.)
EOF
