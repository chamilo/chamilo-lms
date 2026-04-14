#!/bin/bash
set -e

# Determine the target memory limit for the build phase.
# PHP_MEMORY_LIMIT secret overrides the default; fall back to 512M.
_MEM_LIMIT="${PHP_MEMORY_LIMIT:-512M}"

# Write ~/.php.ini so PHP CLI picks it up as the user ini for every spawned
# subprocess (including Composer post-install scripts like assets:install).
cat > ~/.php.ini <<EOF
memory_limit = ${_MEM_LIMIT}
max_execution_time = 0
date.timezone = America/Sao_Paulo
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
display_errors = Off
log_errors = On
EOF

# PHPRC points PHP CLI to the project-root php.ini for all invocations.
# Dynamically set its memory_limit so subprocesses that load via PHPRC also
# receive the correct limit when PHP_MEMORY_LIMIT is set.
export PHPRC="$(pwd)"
sed -i "s/^memory_limit\s*=.*/memory_limit = ${_MEM_LIMIT}/" php.ini

export COMPOSER_MEMORY_LIMIT=-1

# Hard gate: confirm the effective limit before running Composer so the build
# fails immediately if the configuration is not applied correctly.
_EFFECTIVE_MEM="$(php -r 'echo ini_get("memory_limit");')"
echo "PHP memory_limit for build: ${_EFFECTIVE_MEM} (target: ${_MEM_LIMIT})"
if [ "${_EFFECTIVE_MEM}" != "${_MEM_LIMIT}" ]; then
    echo "ERROR: PHP memory_limit (${_EFFECTIVE_MEM}) does not match target (${_MEM_LIMIT}). Aborting build."
    exit 1
fi

composer install --no-dev --optimize-autoloader
yarn build
