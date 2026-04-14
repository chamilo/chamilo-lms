# Chamilo LMS 2.x — Inventário de Customizações

---
Version: 1.0
Last updated: 2026-04-14 (FASE 0)
Status: Active
Owner: Project maintainer

---

> **Regra de ouro**: Todo código novo vai em `public/plugin/[nome]/` ou em arquivos com sufixo `_custom`.
> Nunca edite arquivos do core sem documentar o diff, o motivo e a versão afetada neste arquivo.

---

## Inventário Completo

| Data | Arquivo / Diretório | Tipo | Motivo | Risco de Conflito | Estratégia |
|---|---|---|---|---|---|
| 2026-04-12 | `start.sh` (criado) | Config / Infra | Script de inicialização Replit: MySQL, JWT, cache, PHP server | Baixo — arquivo específico do Replit, não existe no upstream | Manter como arquivo Replit-only, documentar mudanças |
| 2026-04-12 | `.replit` (criado) | Config / Infra | Configuração de workflows, portas e deployment Cloud Run | Baixo — arquivo específico do Replit | Manter separado do código da aplicação |
| 2026-04-12 | `replit.nix` (criado) | Config / Infra | Dependências Nix: mysql80, php82Extensions.xsl | Baixo — específico do ambiente Nix | Atualizar junto com atualizações de PHP/MySQL |
| 2026-04-13 | `start.sh` — MySQL init | Config / Infra | Adicionado `mkdir -p /home/runner/mysql_data` e `mysqld --initialize-insecure` para primeira execução | Baixo | Parte do start.sh; documentado em replit.md |
| 2026-04-13 | `start.sh` — socket symlink | Config / Infra | Symlink `/run/mysqld/mysqld.sock → /home/runner/mysql_run/mysql.sock` para que PDO resolva `localhost` corretamente | Baixo | Parte do start.sh |
| 2026-04-13 | `start.sh` — `php -S` flags | Config / Infra | `-d pdo_mysql.default_socket`, `-d mysqli.default_socket`, `-d memory_limit`, `-d upload_max_filesize`, `-d post_max_size`, `-d date.timezone`, `-d max_execution_time` | Baixo | Parte do start.sh |
| 2026-04-13 | `composer.json` — remoção `twig/inky-extra` | Dependência | Pacote não utilizado; dependência `lorenzo/pinky` exigia `ext-xsl` ausente no Cloud Run build | Baixo | Remover é seguro — nenhum template usa Inky no projeto |
| 2026-04-13 | `composer.lock` | Dependência | Regenerado após remoção de `twig/inky-extra` e `lorenzo/pinky` | Baixo | Versionar normalmente |
| 2026-04-14 | `public/main/install/` — remoção | Segurança | Removido pós-instalação para impedir re-execução do wizard | Nenhum — instalação já concluída | Não restaurar; se necessário reinstalar, recriar a partir do upstream |
| 2026-04-14 | `config/packages/`, `config/routes/`, `config/routes.yaml`, `config/services.yaml`, `config/bundles.php`, `config/preload.php` — chmod 0555 | Segurança | Hardening pós-instalação: arquivos config core somente leitura | Baixo — aplicado em runtime via start.sh; não é alteração de conteúdo | chmod aplicado a cada inicialização em start.sh |
| 2026-04-14 | `src/CoreBundle/DataFixtures/SettingsValueTemplateFixtures.php` — linha 171-174 | Segurança | Substituídos valores de exemplo de credenciais realistas por placeholders óbvios (`your-gotify-token-here`, etc.) para eliminar falso positivo de scanner de secrets | Baixo — apenas valores de exemplo, não código de produção | Manter placeholders; não reverter para valores realistas |
| 2026-04-14 | `public/check.php` — **removido** | Segurança | Symfony Requirements Checker (legado Symfony 2/3/4) exposto publicamente com HTTP 200 via proxy Replit (proteção localhost 127.0.0.1 ineficaz em `php -S` atrás de proxy mTLS). Sem referências internas confirmadas por `grep`. Irrelevante para Symfony 6.4. | Nenhum — nenhuma referência interna encontrada | Não restaurar; se diagnóstico de requisitos for necessário, usar `php bin/console about` |

---

## Arquivos NÃO versionados (gitignore)

| Arquivo / Diretório | Motivo |
|---|---|
| `vendor/` | Dependências Composer — gerado via `composer install` |
| `public/build/` | Assets compilados — gerado via `yarn build` |
| `var/cache/` | Cache Symfony — gerado em runtime |
| `var/log/` | Logs — gerado em runtime |
| `config/jwt/*.pem` | Chaves JWT — geradas em runtime por `start.sh` |

---

## Arquivos que NÃO foram modificados

Os seguintes arquivos foram **identificados como possíveis candidatos** mas permaneceram intactos:

| Arquivo | Motivo de NÃO modificar |
|---|---|
| `src/CoreBundle/Entity/*.php` | Sem necessidade — schema já atende ao projeto |
| `config/packages/*.yaml` | Configuração padrão adequada para o ambiente |
| `assets/vue/` | Sem customizações de frontend ainda |
| `public/plugin/HelloWorld/` | Plugin de exemplo — não utilizado em produção |

---

## Changelog

| Versão | Data | Autor | Descrição |
|---|---|---|---|
| 1.0 | 2026-04-14 | Agent | Inventário inicial completo pós-instalação |
| 1.1 | 2026-04-14 | Agent | FASE 0: remoção de public/check.php (exposto, sem referências internas, legado Symfony 2/3/4) |
