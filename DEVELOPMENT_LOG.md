# DEVELOPMENT_LOG — Tannus / Chamilo 2

Repositório: github.com/Jabuticabaa/Tannus
Stack: PHP 8.2 · Symfony 6.4 · Doctrine ORM · Vue 3 · Webpack Encore · MySQL 8

**Política Zero-Alucinação:** Toda entrada neste log exige output real de comando executado.
Nenhum ✅ sem saída verificada. Nenhuma descrição substitui o output literal do terminal.

---

| Data | Fase | Ação | Arquivos Afetados | Resultado Real | Fonte |
|------|------|------|-------------------|----------------|-------|
| 2026-04-14 | F0 | Verificar `/install/` — `ls -la public/main/install/` | — | `✅ /install/ não encontrado` (ls: exit 1) | docs.chamilo.org |
| 2026-04-14 | F0 | HTTP `/main/install/index.php` — `curl -w "%{http_code}"` | — | `404` | — |
| 2026-04-14 | F0 | HTTP `/` — `curl -w "%{http_code}"` | — | `200` | — |
| 2026-04-14 | F0 | HTTP `/check.php` antes da remoção — `curl -w "%{http_code}"` | — | `200` ⚠️ exposto | — |
| 2026-04-14 | F0 | Buscar referências internas a `check.php` — `grep -r "check\.php" public/ src/ config/ templates/` | — | Sem resultados (exit 1) — nenhuma referência interna | — |
| 2026-04-14 | F0 | Remover `public/check.php` — `rm public/check.php` | `public/check.php` | `✅ check.php removido` · `✅ confirmado ausente` | INSTRUÇÃO MESTRA §T0.2 |
| 2026-04-14 | F0 | HTTP `/check.php` após remoção — `curl -w "%{http_code}"` | — | `404` ✅ protegido | — |
| 2026-04-14 | F0 | HTTP `/` após remoção — `curl -w "%{http_code}"` | — | `200` ✅ app funcional | — |

---

## Contexto dos arquivos removidos

### public/check.php (removido 2026-04-14)
- **O que era:** Symfony Requirements Checker (legado Symfony 2/3/4, `SymfonyRequirements`)
- **Por que estava presente:** Incluído pelo boilerplate upstream do Chamilo; não removido após instalação
- **Proteção original:** `!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])` → HTTP 403 para não-localhost
- **Por que insuficiente:** `php -S` em Replit recebe requisições via proxy mTLS — `REMOTE_ADDR` é o IP do proxy, não `127.0.0.1`. O arquivo estava sendo servido com HTTP 200 para chamadas externas (confirmado via curl)
- **Referências internas:** Nenhuma (grep confirmado)
- **Chamilo 2.0 / Symfony 6.4:** Arquivo irrelevante — `SymfonyRequirements` foi descontinuado após Symfony 4. Nenhuma funcionalidade da aplicação depende dele.
- **Decisão:** Remover. Risco zero. ✅
- **Hash antes da remoção:** `c9f90929c2195afb25226ec0511bd8cd` (429 linhas)

---

## Entradas futuras

Próximas tarefas a serem adicionadas aqui conforme executadas:
- FASE 1 (#6): Auditoria completa de runtime (T1.1–T1.5)
- FASE 2.2 (#8): MySQL timezone alignment
- FASE 2.3 (#7): Secrets hardening + .env.example
- FASE 2.1 + FASE 3/4 (#10): Race condition build fix + docs sync
