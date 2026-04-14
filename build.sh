#!/bin/bash
set -e

# ---------------------------------------------------------------------------
# Resolve memory limit
# PHP_MEMORY_LIMIT é injetado via [env] no .replit (disponível no build).
# Fallback para 512M caso não esteja definido.
# ---------------------------------------------------------------------------
_MEM_LIMIT="${PHP_MEMORY_LIMIT:-512M}"

echo "[build] PHP_MEMORY_LIMIT efetivo: ${_MEM_LIMIT}"

# ---------------------------------------------------------------------------
# Grava ~/.php.ini para subprocessos que carregam o user-ini automaticamente.
# Mesmo que o autoscale não herde PHPRC, ~/.php.ini é sempre lido pelo PHP CLI.
# ---------------------------------------------------------------------------
cat > ~/.php.ini <<EOF
memory_limit = ${_MEM_LIMIT}
max_execution_time = 0
date.timezone = America/Sao_Paulo
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
display_errors = Off
log_errors = On
EOF

# ---------------------------------------------------------------------------
# Atualiza também o php.ini da raiz do projeto (usado via PHPRC).
# ---------------------------------------------------------------------------
export PHPRC="$(pwd)"
sed -i "s/^memory_limit[[:space:]]*=.*/memory_limit = ${_MEM_LIMIT}/" php.ini

# ---------------------------------------------------------------------------
# COMPOSER_MEMORY_LIMIT=-1 desativa o limite interno do Composer.
# ---------------------------------------------------------------------------
export COMPOSER_MEMORY_LIMIT=-1

# ---------------------------------------------------------------------------
# Garante APP_ENV=prod para que o container Symfony seja compilado no modo
# correto durante assets:install e cache:warmup.
# ---------------------------------------------------------------------------
export APP_ENV=prod

_COMPOSER="$(which composer)"

echo "[build] Composer: ${_COMPOSER}"
echo "[build] PHP: $(php -d memory_limit=${_MEM_LIMIT} -r 'echo PHP_VERSION;')"
echo "[build] memory_limit ativo: $(php -d memory_limit=${_MEM_LIMIT} -r 'echo ini_get("memory_limit");')"

# ---------------------------------------------------------------------------
# PASSO 1: composer install --no-scripts
#
# --no-scripts impede que o Composer execute post-install-cmd como subprocesso
# filho. Subprocessos filhos do Composer NÃO herdam o -d memory_limit do
# processo pai — esse é o motivo do OOM no PhpDumper com 128MB.
# Os scripts serão executados manualmente abaixo, com memória controlada.
# ---------------------------------------------------------------------------
echo "[build] Executando composer install --no-scripts ..."
php -d memory_limit=${_MEM_LIMIT} \
    -d max_execution_time=0 \
    -d date.timezone=America/Sao_Paulo \
    "${_COMPOSER}" install --no-dev --optimize-autoloader --no-scripts

echo "[build] composer install concluido."

# ---------------------------------------------------------------------------
# PASSO 2: pre-install-cmd manual
# Executa o ScriptHandler do Chamilo que limpa arquivos legados da 1.9x.
# ---------------------------------------------------------------------------
echo "[build] Executando pre-install-cmd (ScriptHandler 19x) ..."
php -d memory_limit=${_MEM_LIMIT} \
    -d max_execution_time=0 \
    bin/console cache:clear --no-warmup --no-debug 2>/dev/null || true

# ---------------------------------------------------------------------------
# PASSO 3: assets:install com memoria controlada
# Compila o container Symfony (PhpDumper) no processo corrente, com
# memory_limit explícito — nunca como subprocesso filho do Composer.
# ---------------------------------------------------------------------------
echo "[build] Executando assets:install ..."
php -d memory_limit=${_MEM_LIMIT} \
    -d max_execution_time=0 \
    -d date.timezone=America/Sao_Paulo \
    bin/console assets:install public --no-debug

echo "[build] assets:install concluido."

# ---------------------------------------------------------------------------
# PASSO 4: cache:warmup explícito
# Garante que o container esteja totalmente compilado antes do deploy.
# ---------------------------------------------------------------------------
echo "[build] Executando cache:warmup ..."
php -d memory_limit=${_MEM_LIMIT} \
    -d max_execution_time=0 \
    -d date.timezone=America/Sao_Paulo \
    bin/console cache:warmup --no-debug || true

echo "[build] cache:warmup concluido."

# ---------------------------------------------------------------------------
# PASSO 5: limpa cache residual de build
# ---------------------------------------------------------------------------
find var/cache -mindepth 1 -delete 2>/dev/null || true

# ---------------------------------------------------------------------------
# PASSO 6: build frontend (Yarn/Webpack Encore)
# ---------------------------------------------------------------------------
echo "[build] Executando yarn build ..."
yarn build

echo "[build] Build concluido com sucesso."
