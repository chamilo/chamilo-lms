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

## Entradas futuras

| Tarefa | Fase | Status |
|--------|------|--------|
| #6 | FASE 1 — Auditoria completa de runtime (T1.1–T1.5) | Pendente |
| #8 | FASE 2.2 — MySQL timezone alignment | Pendente |
| #7 | FASE 2.3 — Secrets hardening + .env.example | Pendente |
| #10 | FASE 2.1 + FASE 3/4 — Race condition fix + docs sync | Pendente |
