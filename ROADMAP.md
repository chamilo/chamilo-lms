# Chamilo LMS 2.x — Roadmap de Desenvolvimento

---
Version: 1.3
Last updated: 2026-04-14 (FASE 2.1 — fechamento do ciclo de hardening)
Status: Active
Owner: Project maintainer

---

## Funcionalidades já existentes no core (NÃO recriar)

O Chamilo 2.x inclui nativamente os seguintes sistemas. Desenvolver alternativas próprias
criaria conflito e regressões.

| Funcionalidade | Localização no código |
|---|---|
| Gestão de usuários (CRUD, perfis, papéis) | `src/CoreBundle/Entity/User.php` + controllers |
| Gestão de cursos e categorias | `src/CourseBundle/` |
| Sessões de treinamento (grupos de cursos) | `src/CoreBundle/Entity/Session.php` |
| Sistema de exercícios / quizzes | `public/main/exercise/` + entidades `c_quiz_*` |
| Fórum de discussão | `public/main/forum/` |
| Videoconferência (BBB) | `public/plugin/Bbb/` |
| LTI (integração com ferramentas externas) | `src/LtiBundle/` |
| Sistema de plugins | `public/plugin/` (56 plugins nativos) |
| Autenticação JWT + OAuth | LexikJWT + KnpuOAuth2 |
| API REST / GraphQL | API Platform 3.0 |
| Notificações push | Settings: `push_notification_settings` (Gotify + VAPID) |
| Gestão de certificados | `public/plugin/CustomCertificate/` |
| Calendário de aprendizagem | `public/plugin/LearningCalendar/` |
| Compra de cursos | `public/plugin/BuyCourses/` |
| LDAP | `symfony/ldap` + configuração |
| Multi-portal (multi-tenant) | Tabela `access_url` |
| i18n / multi-idioma | `translations/` + `symfony/intl` |

---

## Gaps identificados — o que é necessário que o core não resolve sozinho

### 🔴 Crítico — bloqueadores para produção real

| Gap | Descrição | Impacto |
|---|---|---|
| **Banco de dados persistente** | MySQL local no Cloud Run é efêmero: todos os dados são perdidos a cada redeploy | Perda total de dados em produção |
| **Servidor web adequado** | `php -S` é single-thread; colapsa com múltiplos usuários simultâneos | Indisponibilidade em produção |
| **Secrets em texto plano no .env** | `APP_SECRET`, senha do banco e outros valores sensíveis estão no `.env` commitado | Risco de segurança se o repositório for público |

### 🟡 Importante — afeta qualidade e manutenibilidade

| Gap | Descrição | Impacto |
|---|---|---|
| **Dados MySQL não persistem entre restarts de dev** | A cada reinício do workflow, o banco é re-inicializado | Perda de dados de testes e configurações |
| **System timezone vs PHP runtime timezone** | PHP runtime já configurado como `America/Sao_Paulo` (flag `-d` em `start.sh`), mas o sistema operacional Nix e o MySQL ainda usam UTC. `@@global.time_zone = SYSTEM` confirmado em auditoria FASE 1. | UX / dados — divergência de 3h nos timestamps |
| **xsl extension não carregada** | `pkgs.php82Extensions.xsl` declarado em `replit.nix` mas `php --ri xsl` retorna "Extension 'xsl' not present." Confirmado em FASE 1. `twig/inky-extra` foi removido em Task #5 por depender de xsl. | Qualquer código XSLT falhará silenciosamente |
| **Doctrine migrations: tabela `version` ausente** | Banco criado pelo wizard (317 tabelas diretas). `doctrine:migrations:status` mostra 0/344 executadas e tabela `version` inexistente. Rodar `doctrine:migrations:migrate` sem preparação causará erros. Ação correta (com autorização): `doctrine:migrations:sync-metadata-storage` + `doctrine:migrations:version --add --all` | Risco de breaking change em updates futuros |
| **PHP CLI timezone = UTC** | `php -r "echo date_default_timezone_get();"` retorna UTC. `php -S` usa `-d date.timezone=America/Sao_Paulo`. Scripts `bin/console` e cron jobs rodam em UTC. | Timestamps em logs CLI podem divergir do servidor |
| **Frontend build race condition** | `start.sh` inicia `yarn build ... &` em background enquanto `php -S` já aceita conexões. Se `entrypoints.json` for apagado, primeiras requisições retornam 500. | Instabilidade em fresh containers |
| **Sem backup automatizado** | Nenhuma rotina de backup do banco configurada | Risco de perda de dados |
| **Sem monitoramento/alertas** | Nenhum sistema de health check ou alertas configurado | Sem visibilidade de falhas |

### 🟢 Nice-to-have — melhorias futuras

| Gap | Descrição |
|---|---|
| Temas customizados | Nenhuma customização visual além do padrão Chamilo |
| Plugins custom | Nenhum plugin próprio criado ainda |
| Integração de e-mail transacional | DSN configurado como `null://null` — e-mails desativados |
| Integração com sistemas externos | Via LTI ou API Platform (base já existe, não configurado) |

---

## Extensões planejadas

| Funcionalidade | Estratégia | Risco de Breaking Change | Prioridade |
|---|---|---|---|
| Migrar banco para externo (PlanetScale / Railway / RDS) | Atualizar `DATABASE_URL` no Replit Secrets; ajustar `start.sh` | Baixo | 🔴 Alta |
| Substituir `php -S` por PHP-FPM + Nginx no Cloud Run | Criar `Dockerfile` customizado para Cloud Run | Médio | 🔴 Alta |
| Mover secrets para Replit Secrets (não no `.env`) | Replit Secrets → env vars de runtime | Baixo | 🔴 Alta |
| Configurar e-mail transacional | Atualizar `MAILER_DSN` no `.env` / Secrets | Baixo | 🟡 Média |
| Plugin custom de relatórios | Criar em `public/plugin/RelatoriosCustom/` | Baixo | 🟢 Baixa |
| Tema visual customizado | Criar em `assets/css/themes/custom/` | Baixo | 🟢 Baixa |
| Configurar cron jobs | Via Cloud Run Scheduler ou Symfony Messenger | Médio | 🟡 Média |

---

## Regras de atualização segura (para futuras versões do Chamilo 2.x)

1. **Checar CHANGELOG.md** antes de qualquer `composer update` — identificar breaking changes
2. **Fazer backup completo do banco** antes de rodar migrations (`bin/console doctrine:migrations:migrate`)
3. **Verificar customizações em `CUSTOMIZATIONS.md`** — confirmar que nenhuma alteração conflita com o upstream
4. **Rodar em ambiente de staging** antes de produção (ex: branch separado no Replit)
5. **Nunca rodar `composer update` em produção** — sempre testar localmente primeiro
6. **Após update**: limpar cache com `php bin/console cache:clear` e rebuildar frontend com `yarn build`
7. **Extensões PHP**: verificar `replit.nix` após update se novas extensões forem necessárias

---

## Roadmap Now / Next / Later

### Now (em execução)
| Iniciativa | Objetivo | Métrica de sucesso |
|---|---|---|
| Ambiente Replit funcional | App rodando para demo e desenvolvimento | HTTP 200 na raiz, banco conectado |
| Documentação de arquitetura | Base para desenvolvimento futuro | ARCHITECTURE.md, CUSTOMIZATIONS.md, ROADMAP.md presentes |
| Hardening de segurança básico | Instalador removido, config protegida | /main/install/ retorna 404 |

### Next (próximos passos prioritários)
| Iniciativa | Por quê agora | Dependência |
|---|---|---|
| Banco de dados externo persistente | Pré-requisito para qualquer dado real de produção | Escolher provedor (PlanetScale, Railway, etc.) |
| Secrets no Replit Secrets (não no .env) | DATABASE_PASSWORD, APP_SECRET, JWT_PASSPHRASE confirmados hardcoded em auditoria FASE 1 | — (Task #7) |
| ~~Alinhar timezone MySQL → America/Sao_Paulo~~ | ✅ RESOLVIDO — `SET GLOBAL time_zone = '-03:00'` via start.sh; UNIX_TIMESTAMP diff = 0s | Task #8 concluída |
| ~~Corrigir race condition `yarn build &` em start.sh~~ | ✅ RESOLVIDO — build síncrono + `PIPESTATUS` exit code check; PHP server não arranca antes do build | Task #10 concluída |
| Investigar xsl extension (declarada mas não ativa) | `php --ri xsl` → "not present" apesar de estar em replit.nix | — |
| Inicializar tabela Doctrine migrations (pós-wizard) | `doctrine:migrations:sync-metadata-storage` + `--add --all` — sem isso updates futuros são arriscados | Autorização explícita necessária |
| Ativar e-mail transacional | Notificações de plataforma não funcionam (MAILER_DSN=null://null) | Escolher provedor SMTP |

### Later (direcional)
- Tema visual customizado
- Plugins próprios de extensão
- Monitoramento e alertas de saúde
- Pipeline CI/CD para deploy automatizado
- Servidor PHP-FPM + Nginx para produção real

---

## Changelog

| Versão | Data | Autor | Descrição |
|---|---|---|---|
| 1.0 | 2026-04-14 | Agent | Criação inicial — pós-auditoria Fase 1 |
| 1.1 | 2026-04-14 | Agent | FASE 1 auditoria: novos gaps xsl, migrations version table, race condition, CLI timezone |
| 1.2 | 2026-04-14 | Agent | FASE 2.2 (Task #8): Gap #4 resolvido — MySQL timezone alinhada com PHP via `-03:00` offset em start.sh |
| 1.3 | 2026-04-14 | Agent | FASE 2.1 (Task #10): Race condition corrigida — build síncrono + exit code check |
