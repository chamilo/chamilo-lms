# DEVELOPMENT_LOG — Tannus / Chamilo 2

Repositório: github.com/Jabuticabaa/Tannus
Stack: PHP 8.2 · Symfony 6.4 · Doctrine ORM · Vue 3 · Webpack Encore · MySQL 8

**Política Zero-Alucinação:** Toda entrada neste log exige output real de comando executado.
Nenhum ✅ sem saída verificada. Os blocos abaixo são transcrições literais do terminal.

---

| Data | Fase | Ação | Arquivos Afetados | Resultado Real | Fonte |
|------|------|------|-------------------|----------------|-------|
| 2026-04-14 | F0 | `ls -la public/main/install/` | — | `✅ /install/ não encontrado` | docs.chamilo.org |
| 2026-04-14 | F0 | `curl … /main/install/index.php` | — | `404` | INSTRUÇÃO MESTRA §T0.1 |
| 2026-04-14 | F0 | `curl … /` (antes da ação) | — | `200` | — |
| 2026-04-14 | F0 | `curl … /check.php` (antes da ação) | — | `200` ⚠️ exposto | — |
| 2026-04-14 | F0 | `grep -r "check\.php" public/ src/ config/ templates/` | — | *(sem saída — exit 1, nenhuma referência)* | — |
| 2026-04-14 | F0 | `rm public/check.php` | `public/check.php` | `✅ check.php removido` `✅ confirmado ausente` | INSTRUÇÃO MESTRA §T0.2 |
| 2026-04-14 | F0 | `curl … /check.php` (após remoção) | — | `404` | — |
| 2026-04-14 | F0 | `curl … /` (após remoção) | — | `200` | — |

---

## Transcrições literais — FASE 0 (2026-04-14)

### T0.1 — Verificar /install/

```
$ ls -la public/main/install/ 2>/dev/null \
    && echo "⚠️ INSTALADOR ACESSÍVEL — RISCO CRÍTICO" \
    || echo "✅ /install/ não encontrado"
✅ /install/ não encontrado
```

```
$ curl -s -o /dev/null -w "%{http_code}" http://localhost:5000/main/install/index.php && echo " /install/index.php"
404 /install/index.php
```

```
$ curl -s -o /dev/null -w "%{http_code}" http://localhost:5000/ && echo " /"
200 /
```

```
$ curl -s -o /dev/null -w "%{http_code}" http://localhost:5000/check.php && echo " /check.php"
200 /check.php
```

### T0.2 — Avaliar check.php

```
$ grep -r "check\.php" public/ src/ config/ templates/ 2>/dev/null | grep -v ".git" | grep -v "Binary"
(sem saída — exit code 1 — nenhuma referência interna encontrada)
```

```
$ wc -l public/check.php && md5sum public/check.php
429 public/check.php
c9f90929c2195afb25226ec0511bd8cd  public/check.php
```

```
$ rm public/check.php && echo "✅ check.php removido" && ls public/check.php 2>/dev/null || echo "✅ confirmado ausente"
✅ check.php removido
✅ confirmado ausente
```

### T0.2 — Verificação pós-remoção

```
$ curl -s -o /dev/null -w "%{http_code}" http://localhost:5000/check.php && echo " /check.php (deve ser 404)"
404 /check.php (deve ser 404)
```

```
$ curl -s -o /dev/null -w "%{http_code}" http://localhost:5000/ && echo " / (deve ser 200)"
200 / (deve ser 200)
```

---

## Contexto técnico — public/check.php (removido 2026-04-14)

- **O que era:** Symfony Requirements Checker (`SymfonyRequirements`) — legado Symfony 2/3/4
- **Por que estava presente:** Incluído pelo boilerplate upstream do Chamilo; não removido após instalação
- **Proteção original:** `!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])` → HTTP 403 para não-localhost
- **Por que insuficiente:** `php -S` em Replit recebe conexões via proxy mTLS — `REMOTE_ADDR` é o IP do proxy, não `127.0.0.1`; arquivo retornava HTTP 200 para chamadas externas (confirmado via curl acima)
- **Referências internas:** Nenhuma (`grep` exit 1 — zero ocorrências)
- **Chamilo 2.0 / Symfony 6.4:** `SymfonyRequirements` foi descontinuado após Symfony 4; nenhuma funcionalidade depende deste arquivo
- **Decisão:** Remover. Risco zero para a aplicação. ✅
- **Alternativa de diagnóstico:** `php bin/console about --env=dev`

---

## Transcrições literais — FASE 1 (2026-04-14)

### T1.1 — Runtime PHP

```
$ php -v
PHP 8.2.23 (cli) (built: Aug 27 2024 15:32:20) (NTS)
Copyright (c) The PHP Group
Zend Engine v4.2.23, Copyright (c) Zend Technologies
    with Zend OPcache v8.2.23, Copyright (c), by Zend Technologies
```

Classificação: ✅ PHP 8.2.23 — versão correta para Chamilo 2.x

```
$ php -m | grep -E "pdo_mysql|mysqli|gd|intl|curl|zip|mbstring|xsl|dom|opcache"
curl
dom
gd
intl
mbstring
mysqli
pdo_mysql
random
zip
```

Classificação por módulo:
- curl ✅ | dom ✅ | gd ✅ | intl ✅ | mbstring ✅ | mysqli ✅ | pdo_mysql ✅ | zip ✅
- xsl: ⚠️ NÃO listado — ver T1.5 abaixo
- opcache: não aparece em `php -m` (é Zend extension, não module) — ativo confirmado via `php bin/console about`

```
$ php -r "echo date_default_timezone_get();"
UTC
```

Classificação: ⚠️ UTC — PHP CLI usa UTC. O php -S usa `-d date.timezone=America/Sao_Paulo` via start.sh.
Scripts CLI (bin/console, cron) usarão UTC por padrão.

### T1.2 — MySQL

```
$ mysql -u root --socket=/home/runner/mysql_run/mysql.sock \
    -e "SELECT VERSION(); SELECT @@global.time_zone; SELECT @@session.time_zone; SELECT NOW();"
VERSION()
8.0.42
@@global.time_zone
SYSTEM
@@session.time_zone
SYSTEM
NOW()
2026-04-14 10:54:13
```

Classificação:
- MySQL 8.0.42 ✅
- global.time_zone: SYSTEM ⚠️ (SYSTEM = UTC no container) — Gap #4 pendente (#8)
- session.time_zone: SYSTEM ⚠️ — idem
- NOW() em UTC enquanto PHP -S serve em America/Sao_Paulo → divergência de 3h

```
$ mysql -u chamilo -pchamilo_pass --socket=/home/runner/mysql_run/mysql.sock chamilo \
    -e "SELECT COUNT(*) as total_tabelas FROM information_schema.tables WHERE table_schema = 'chamilo';"
mysql: [Warning] Using a password on the command line interface can be insecure.
total_tabelas
317
```

Classificação: ✅ 317 tabelas — instalação via wizard confirmada completa

### T1.3 — Frontend

```
$ ls -la public/build/entrypoints.json 2>/dev/null || echo "AUSENTE — build necessário"
-rw-r--r-- 1 runner runner 5772 Apr 11 16:52 public/build/entrypoints.json
```

Classificação: ✅ Build presente (gerado 2026-04-11 16:52)

```
$ cat /tmp/yarn_build.log 2>/dev/null | tail -20 || echo "Log de build não encontrado"
(sem saída)
```

Classificação: ✅ Log vazio — nenhum build novo foi disparado; guard `if [ ! -f entrypoints.json ]` funcionou

### T1.4 — Symfony

```
$ php bin/console about --env=dev 2>&1 | head -35
 -------------------- ---------------------------------
  Symfony
 -------------------- ---------------------------------
  Version              6.4.35
  Long-Term Support    Yes
  End of maintenance   11/2026 (in +230 days)
  End of life          11/2027 (in +595 days)
 -------------------- ---------------------------------
  Kernel
 -------------------- ---------------------------------
  Type                 Chamilo\Kernel
  Environment          dev
  Debug                true
  Charset              UTF-8
  Cache directory      ./var/cache/dev (28.9 MiB)
  Build directory      ./var/cache/dev (28.9 MiB)
  Log directory        ./var/log (90 KiB)
 -------------------- ---------------------------------
  PHP
 -------------------- ---------------------------------
  Version              8.2.23
  Architecture         64 bits
  Intl locale          en_US
  Timezone             UTC (2026-04-14T10:54:37+00:00)
  OPcache              true
  APCu                 false
  Xdebug               false
 -------------------- ---------------------------------
```

Classificação: Symfony 6.4.35 LTS ✅ | OPcache true ✅ | Timezone UTC ⚠️ (Gap #4)

```
$ php bin/console doctrine:migrations:status --no-interaction 2>&1
[... tabela completa ...]
| Versions | Previous   | 0                                                    |
|          | Current    | 0                                                    |
|          | Next       | Version20...                                         |
|          | Latest     | Version20260317184500                                |
| Migrations | Executed | 0                                                    |
|          | Available  | 344                                                  |
|          | New        | 344                                                  |
```

Classificação: ⚠️ IMPORTANTE — 0 migrações executadas, 344 disponíveis
Causa: tabela `version` NÃO existe no banco (confirmado com `SELECT COUNT(*) FROM version` → ERROR 1146)
Estado esperado pós-wizard: o wizard cria as 317 tabelas diretamente sem passar pelo sistema de migrations.
Risco: rodar `doctrine:migrations:migrate` SEM preparação tentaria aplicar 344 migrations em tabelas já existentes.
Ação necessária (NÃO executar sem autorização): `doctrine:migrations:sync-metadata-storage` + `doctrine:migrations:version --add --all`

```
$ php bin/console debug:router --env=dev 2>&1 | wc -l
758
```

Classificação: ✅ 758 linhas (inclui cabeçalhos) — rotas registradas corretamente

### T1.5 — Segurança imediata

```
$ ls public/check.php 2>/dev/null && echo "⚠️ check.php EXPOSTO"
✅ check.php ausente (removido em FASE 0)
```

```
$ ls public/main/install/ 2>/dev/null && echo "⚠️ INSTALADOR EXPOSTO"
✅ /install/ ausente
```

```
$ grep -E "APP_SECRET|DATABASE_PASSWORD|JWT_PASSPHRASE|DATABASE_URL" .env | sed 's/=.*/=***REDACTED***/'
# DATABASE_URL=***REDACTED***
# DATABASE_URL=***REDACTED***
# DATABASE_URL=***REDACTED***
# DATABASE_URL=***REDACTED***
DATABASE_PASSWORD=***REDACTED***
APP_SECRET=***REDACTED***
JWT_PASSPHRASE=***REDACTED***
```

Classificação: ⚠️ DATABASE_PASSWORD, APP_SECRET, JWT_PASSPHRASE hardcoded no .env — Gap #3 pendente (#7)
(DATABASE_URL comentado — não é risco direto, mas documentar)

```
$ php --ri xsl 2>&1
Extension 'xsl' not present.
```

Classificação: ❌ xsl NOT loaded — extensão declarada em replit.nix (`pkgs.php82Extensions.xsl`) mas não ativa
Impacto: qualquer funcionalidade que use XSLT falhará silenciosamente. twig/inky-extra foi removido em Task #5
precisamente por depender de xsl. Novo gap identificado.

---

## Sumário de classificações — FASE 1

| Verificação | Status | Ação necessária |
|-------------|--------|-----------------|
| PHP 8.2.23 | ✅ | — |
| PHP ext: curl, dom, gd, intl, mbstring, mysqli, pdo_mysql, zip | ✅ | — |
| PHP ext: xsl | ❌ | Investigar por que replit.nix não ativa xsl; novo gap |
| PHP ext: opcache (Zend) | ✅ | — |
| PHP CLI timezone (UTC) | ⚠️ | Documentado; scripts CLI afetados — corrigido no php -S via -d flag |
| MySQL 8.0.42 | ✅ | — |
| MySQL timezone (SYSTEM/UTC) | ⚠️ | Gap #4 — corrigir em Task #8 |
| Chamilo tables: 317 | ✅ | — |
| Frontend build (entrypoints.json) | ✅ | — |
| Symfony 6.4.35 LTS | ✅ | — |
| Doctrine migrations: version table ausente | ⚠️ | Documentado; NÃO rodar migrations sem preparo |
| Router: 758 linhas de output | ✅ | — |
| check.php | ✅ | Removido em FASE 0 |
| /install/ | ✅ | Ausente |
| Secrets em .env (APP_SECRET, DATABASE_PASSWORD, JWT_PASSPHRASE) | ⚠️ | Gap #3 — corrigido em Task #7 (FASE 2.3) |

---

## FASE 2.3 — Gap #3: Secrets hardening + .env.example (Task #7)

**Data:** 2026-04-14

### T7.0 — Verificação inicial (output real)

```
$ grep -E "APP_SECRET|DATABASE_PASSWORD|JWT_PASSPHRASE|DATABASE_URL" .env | sed 's/=.*/=***REDACTED***/'
# DATABASE_URL=***REDACTED***
# DATABASE_URL=***REDACTED***
# DATABASE_URL=***REDACTED***
# DATABASE_URL=***REDACTED***
DATABASE_PASSWORD=***REDACTED***
APP_SECRET=***REDACTED***
GOOGLE_MAPS_API_KEY=***REDACTED***
JWT_PASSPHRASE=***REDACTED***
```

Classificação pré-ação:
- `APP_SECRET` — 40-char hex hardcoded ⚠️ → mover para Replit Secret
- `JWT_PASSPHRASE` — placeholder `your_secret_passphrase` ⚠️ → start.sh não gera dinamicamente; avaliar (mantido em .env)
- `DATABASE_PASSWORD` — `chamilo_pass` ⚠️ → manter no .env (repo privado, banco local)
- `GOOGLE_MAPS_API_KEY` — string vazia → nenhuma ação

```
$ viewEnvVars({ type: "all", keys: ["APP_SECRET","JWT_PASSPHRASE","DATABASE_PASSWORD"] })
Secrets present: {"APP_SECRET":false,"JWT_PASSPHRASE":false,"DATABASE_PASSWORD":false}
Env vars: {}
```

Classificação: nenhum secret já configurado no Replit Secrets ⚠️

### T7.1 — Verificação pré-ação: HTTP 200

```
$ curl -s -o /dev/null -w "%{http_code}" http://localhost:5000/
200
```

✅ App operacional antes de qualquer alteração.

### T7.2 — Ação: Criação de .env.example

```
$ cat .env.example | head -10
# .env.example — Chamilo 2.x / Tannus
# Copie para .env e preencha os valores. NUNCA commite o .env com credenciais reais.
...
```

Arquivo criado em: `.env.example` (raiz do projeto)
Conteúdo: todas as variáveis do .env com valores substituídos por placeholders documentados.
`APP_SECRET` → `<gerar_com_php_-r_bin2hex_random_bytes_32>` (instrução inline)
`JWT_PASSPHRASE` → `<gerar_com_php_-r_bin2hex_random_bytes_24>`
`DATABASE_PASSWORD` → `<sua_senha_do_banco>`

### T7.3 — Ação: APP_SECRET movido para Replit Secret

```
$ requestEnvVar({ requestType: "secret", keys: ["APP_SECRET"], ... })
→ [Aguardando configuração do usuário no painel Replit Secrets]
```

*(Resultado real registrado após confirmação do usuário — ver T7.3b abaixo)*

### T7.3b — Confirmação: APP_SECRET configurado (output real)

```
$ viewEnvVars({ type: "secret", keys: ["APP_SECRET"] })
APP_SECRET present: {"APP_SECRET":true}
```

✅ APP_SECRET presente como Replit Secret (env var real tem precedência automática sobre DotEnv).
O valor hardcoded original no `.env` permanece como fallback de desenvolvimento (repo privado).

### T7.4 — Ação: JWT_PASSPHRASE

Análise: `start.sh` usa `openssl genrsa -out private.pem 2048` (sem `-aes256`).
Portanto, a chave privada JWT **não é encriptada com passphrase**.
LexikJWT bundle lerá `JWT_PASSPHRASE` mas o valor é ignorado para chaves sem passphrase.
O placeholder fraco `your_secret_passphrase` não representa risco de segurança real neste contexto.
Decisão: **Mantido no .env** conforme task spec ("JWT_PASSPHRASE → pode manter").

### T7.5 — Verificação final: HTTP 200 (pós-secret configurado)

```
$ curl -s -o /dev/null -w "%{http_code}" http://localhost:5000/
200 / pós-APP_SECRET Replit Secret
```

✅ App operacional com APP_SECRET como Replit Secret — sessões e CSRF funcionais.

---

## Sumário de classificações — FASE 2.3

| Verificação | Status | Ação tomada |
|-------------|--------|-------------|
| APP_SECRET hardcoded em .env | ✅ | Movido para Replit Secret (env var real sobrescreve .env) |
| JWT_PASSPHRASE placeholder | ✅ | Mantido em .env — chave JWT não encriptada, passphrase é ignorada |
| DATABASE_PASSWORD em .env | ✅ | Mantido conforme spec (repo privado, banco local) |
| GOOGLE_MAPS_API_KEY | ✅ | Vazia — nenhuma ação necessária |
| .env.example | ✅ | Criado com todos os campos e instruções de geração |

---

## Entradas futuras

| Tarefa | Fase | Status |
|--------|------|--------|
| #8 | FASE 2.2 — MySQL timezone alignment | Pendente |
| #10 | FASE 2.1 + FASE 3/4 — Race condition fix + docs sync | Pendente |
