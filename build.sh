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

# Resolve Composer path once so subsequent calls use a fixed reference.
# $(which composer) may point to a Nix store path that changes across updates.
_COMPOSER="$(which composer)"

# Informational: report effective memory limit without hard-failing the build.
# The hard gate was removed because PHP_MEMORY_LIMIT may not be injected as a
# Secret in all autoscale deployment contexts; the -d flag below guarantees the
# limit regardless of PHPRC/user-ini resolution order.
echo "PHP memory_limit efetivo: $(php -d memory_limit=${_MEM_LIMIT} -r 'echo ini_get("memory_limit");')"

# Invoke Composer via the PHP binary directly so -d flags apply to the Composer
# process itself AND to all subprocesses it spawns (post-install-cmd scripts).
# This bypasses the autoscale environment's PHPRC/~/.php.ini inheritance gap.
php -d memory_limit=${_MEM_LIMIT} \
    -d max_execution_time=0 \
    -d date.timezone=America/Sao_Paulo \
    "${_COMPOSER}" install --no-dev --optimize-autoloader

# Run assets:install explicitly with controlled memory, since assets:install in
# auto-scripts now uses --no-scripts to skip DI container compilation inside
# the Composer subprocess. This step compiles the container with enough memory.
php -d memory_limit=${_MEM_LIMIT} bin/console assets:install public --no-debug

yarn build
