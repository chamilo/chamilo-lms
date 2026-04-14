---
name: chamilo-symfony-architecture
description: >
  Padrões de arquitetura PHP/Symfony para o projeto Tannus/Chamilo 2.
  Use quando for criar, modificar ou auditar código PHP, bundles,
  serviços, controllers, entidades Doctrine ou rotas Symfony.
  Inclui hierarquia de mudança segura e zonas proibidas do core.
---

# Arquitetura PHP/Symfony — Tannus / Chamilo 2

## Antes de Qualquer Alteração

Execute esta checklist em ordem. Não pule etapas:

1. Ler o objetivo completo da tarefa
2. Verificar se já existe feature equivalente no core Chamilo
3. Verificar se a alteração pode ser feita por extensão (plugin, bundle, config) — não editando o core
4. Avaliar impacto em: banco, autenticação, rotas, build frontend, plugins, integrações
5. Registrar plano curto em `DEVELOPMENT_LOG.md` ANTES de executar
6. Só então executar

## Hierarquia de Mudança Segura

Prefira NESTA ORDEM (1 = mais seguro, 5 = último recurso):

1. **Configuração** — arquivos `.yaml`, `.env`, parâmetros Symfony
2. **Extensão/Plugin** — bundle próprio, event listener, decorator
3. **Novo componente** — controller, serviço, entidade em namespace próprio
4. **Override isolado** — sobrescrever apenas o necessário, documentar diff
5. **Patch no core** — APENAS como último recurso; exige justificativa, diff documentado e declaração de risco de update

## Regras PHP/Symfony

- Seguir PSR-12 para estilo de código
- Usar injeção de dependência via construtor — nunca `new` direto de serviços
- Entidades Doctrine: usar annotations ou atributos PHP 8+ — nunca SQL direto em entidade
- Controllers devem ser magros — lógica de negócio em Services
- Nunca usar `$_GET`, `$_POST` ou `$_SESSION` diretamente — usar Request e SessionInterface do Symfony
- Validação sempre via Symfony Validator ou Form — nunca manual inline
- Eventos e hooks: usar EventDispatcher, nunca modificar o core para adicionar comportamento
- `composer update` apenas com motivo forte documentado; preferir `composer install`

## Zonas Seguras de Extensão

- `src/CoreBundle/` — extensões de core devem ser em bundles separados
- `src/Plugins/` — criação de plugins novos
- `config/packages/` — configuração de pacotes
- `config/routes/` — rotas novas

## Zonas de Alto Risco (requer aprovação explícita)

- `public/main/` — código legado; qualquer alteração pode quebrar compatibilidade
- `src/CoreBundle/Entity/` — mudanças em entidades existentes afetam migrations
- `src/CoreBundle/Security/` — autenticação e permissões; risco de regressão crítica
- `assets/` — build frontend; requer pipeline de build

## Verificação de Existência no Core

Antes de criar qualquer funcionalidade, execute:
```bash
grep -r "NomeDaFeature" src/ --include="*.php" -l
php bin/console debug:container | grep "nome_servico"
php bin/console debug:router | grep "nome_rota"
```

Se já existir: documente a descoberta e adapte ao que existe.
