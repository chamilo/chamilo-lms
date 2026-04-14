# Skills Tannus/Chamilo — Guia de Instalação no Replit

## Como instalar no Replit (plano individual)

### Método 1: Upload via Skills Pane (recomendado)
1. Abra seu Replit Workspace no projeto Tannus
2. Clique em **Skills** no painel lateral
3. Clique em **Upload custom skill**
4. Faça upload de cada arquivo `.md` desta pasta

### Método 2: Via arquivo direto (mais rápido)
1. No Replit, ative **Show Hidden Files** no file sidebar
2. Navegue até `/.agents/skills/`
3. Crie cada arquivo `.md` copiando o conteúdo

### Método 3: Via npx CLI
```bash
# No terminal do Replit
mkdir -p .agents/skills
cp *.md .agents/skills/
```

---

## Skills incluídas (ordem de prioridade)

| Arquivo | Quando o agente carrega |
|---|---|
| `chamilo-zero-hallucination.md` | **SEMPRE** — skill mestre, carregue antes de tudo |
| `chamilo-symfony-architecture.md` | Tarefas PHP, Symfony, controllers, serviços |
| `chamilo-database-migrations.md` | Banco de dados, migrations, Doctrine, fixtures |
| `chamilo-replit-environment.md` | Variáveis de ambiente, Secrets, build, deploy |
| `chamilo-security.md` | Auth, JWT, permissões, uploads, inputs |
| `chamilo-frontend.md` | Vue.js, Webpack Encore, assets, templates |
| `chamilo-documentation-log.md` | Atualizar DEVELOPMENT_LOG.md, ARCHITECTURE.md |
| `chamilo-testing.md` | PHPUnit, fixtures controladas, dados de teste |

---

## Uso recomendado

No início de cada sessão com o agente, diga:
> "Carregue a skill chamilo-zero-hallucination antes de qualquer ação."

Para tarefas específicas:
> "Vamos fazer uma migration. Carregue chamilo-zero-hallucination e chamilo-database-migrations."

---

## Atualização das Skills

As skills devem ser atualizadas sempre que:
- Um padrão novo for estabelecido no projeto
- Um bug recorrente for resolvido (skill reativa)
- A stack ou ambiente mudar
- Uma proibição nova for identificada

Registre toda atualização no `DEVELOPMENT_LOG.md` do projeto.
