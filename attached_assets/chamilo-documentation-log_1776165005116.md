---
name: chamilo-documentation-log
description: >
  Protocolo de documentação viva para o projeto Tannus/Chamilo.
  Carregue quando for registrar decisões, atualizar logs de desenvolvimento,
  documentar arquitetura, customizações ou roadmap.
  Define o formato obrigatório de cada artefato de documentação.
---

# Documentação e Log — Tannus / Chamilo

## Artefatos Obrigatórios na Raiz do Repositório

| Arquivo | Propósito | Atualizar quando |
|---|---|---|
| `ARCHITECTURE.md` | Stack real, diretórios, decisões estruturais | Mudanças de stack ou estrutura |
| `CUSTOMIZATIONS.md` | Tudo que difere do upstream Chamilo | Qualquer override, patch ou adição |
| `ROADMAP.md` | O que existe, gaps, melhorias, prioridades | Início/fim de features, mudança de prioridade |
| `DEVELOPMENT_LOG.md` | Log cronológico de cada ação | **TODA ação técnica executada** |
| `.env.example` | Variáveis necessárias sem valores | Adição/remoção de variáveis |
| `replit.md` | Como operar no Replit | Mudança no runtime ou build |

## DEVELOPMENT_LOG.md — Formato Obrigatório

Cada entrada DEVE conter:

```markdown
## [DATA] — [Título da Tarefa]

**Objetivo:** O que se pretendia fazer

**Arquivos afetados:**
- `path/ao/arquivo.php` — descrição da mudança
- `config/packages/novo.yaml` — criado para X

**Comandos executados:**
```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate --no-interaction
```

**Resultado real:** O que de fato aconteceu (não o que era esperado)

**Erros encontrados:** (se houver — registrar exatamente, não resumir)
```
Error: ...
```

**Fonte/Referência:** URL da doc, issue ou commit que embasou a decisão

**Próximos passos:** O que ainda precisa ser feito

**Riscos remanescentes:** O que pode quebrar e como monitorar
```

## ARCHITECTURE.md — Seções Obrigatórias

```markdown
## Stack Real
- PHP X.X + Symfony X.X
- Doctrine ORM X.X
- Vue 3 + Webpack Encore
- MySQL/PostgreSQL X.X
- Node X.X / NPM X.X

## Estrutura de Diretórios
[mapa dos diretórios principais com descrição]

## Zonas Seguras de Extensão
[onde criar código novo sem risco]

## Zonas de Risco
[o que não tocar sem análise profunda]

## Decisões Arquiteturais
[cada decisão importante com data e justificativa]

## Runtime e Build
[como iniciar, buildar e rodar o projeto]
```

## CUSTOMIZATIONS.md — Formato por Entrada

```markdown
### [Nome da Customização]
- **Tipo:** override | plugin | bundle | config | patch
- **Arquivos:** lista de arquivos modificados/criados
- **Risco de conflito em update:** baixo | médio | alto
- **Estratégia:** como isso foi feito e por quê
- **Data:** YYYY-MM-DD
```

## ROADMAP.md — Estrutura

```markdown
## Now (em andamento)
- [ ] Feature X — responsável, prazo estimado

## Next (próximas sprints)
- [ ] Feature Y — depende de Z

## Later (backlog)
- [ ] Feature W — baixa prioridade

## Gaps Identificados (não existe no core)
- Gap A — descrição

## Riscos Declarados
- Risco X — como monitorar
```

## Regra do Agente

O agente DEVE atualizar `DEVELOPMENT_LOG.md` como PRIMEIRO ato antes de qualquer execução e como ÚLTIMO ato após a conclusão de qualquer tarefa. Se não puder atualizar, a tarefa não está concluída.
