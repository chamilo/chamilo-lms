---
name: chamilo-database-migrations
description: >
  Regras rigorosas para operações de banco de dados no Tannus/Chamilo.
  Carregue antes de qualquer operação com Doctrine Migrations, schema,
  queries, fixtures ou alterações estruturais em tabelas.
  Contém o protocolo completo anti-perda de dados.
---

# Banco de Dados e Migrations — Tannus / Chamilo

## Protocolo Obrigatório Antes de Qualquer Migration

Execute TODOS os passos. Não pule nenhum:

```bash
# 1. Inspecionar estado atual das migrations
php bin/console doctrine:migrations:status

# 2. Checar migrations pendentes
php bin/console doctrine:migrations:list

# 3. Gerar diff do schema (NÃO executar ainda)
php bin/console doctrine:migrations:diff --dry-run

# 4. Revisar o SQL gerado
# Leia cada linha do output — entenda o que será alterado

# 5. Identificar risco de perda de dados
# Procure por: DROP TABLE, DROP COLUMN, ALTER TABLE com NOT NULL sem default

# 6. Registrar no DEVELOPMENT_LOG.md
# 7. Executar apenas se validado
```

## Proibições Absolutas

- Nunca executar `DROP TABLE` sem backup validado e aprovação explícita
- Nunca recriar tabela "por conveniência" — use ALTER TABLE
- Nunca "consertar" modelagem manualmente sem documentar o SQL executado
- Nunca rodar migration em produção sem ter rodado em ambiente de teste primeiro
- Nunca usar `--force` em migrations sem entender exatamente o que será feito
- Nunca assumir conteúdo de tabelas sem consulta real ao banco

## Verificação de Schema Atual

Antes de propor qualquer alteração de schema:
```bash
# Ver estrutura de tabela específica
php bin/console doctrine:schema:validate

# Inspecionar tabela no banco diretamente
php bin/console dbal:run-sql "DESCRIBE nome_tabela"

# Ver entidade atual
cat src/CoreBundle/Entity/NomeEntidade.php
```

## Backup Obrigatório

Se houver dados relevantes (não ambiente zerado):
```bash
# MySQL/MariaDB
mysqldump -u user -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# PostgreSQL
pg_dump database_name > backup_$(date +%Y%m%d_%H%M%S).sql
```
Registre o arquivo de backup no `DEVELOPMENT_LOG.md` antes de continuar.

## Regras para Fixtures e Dados de Teste

1. Pedir autorização explícita antes de popular banco
2. Definir escopo mínimo necessário
3. Registrar plano no `DEVELOPMENT_LOG.md`
4. Usar dados claramente marcados: prefixo `TEST_` em usernames, emails `test+*@tannus.dev`
5. Documentar como remover os dados de teste
6. NUNCA criar dados persistentes de usuários reais sem consentimento

## Rollback

Toda migration deve ter método `down()` implementado e testado:
```php
public function down(Schema $schema): void
{
    // Reverter exatamente o que up() fez
    // Se não for possível reverter, documentar explicitamente por quê
}
```

## Detecção de Problemas Comuns no Chamilo

- Tabelas com prefixo `c_` são geralmente de contexto de curso — alto risco
- Tabelas `user`, `access_url`, `session` — afetam autenticação; não alterar sem análise completa
- Chaves estrangeiras podem não estar ativas (depende de configuração MySQL) — sempre verificar antes
