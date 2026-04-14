---
name: chamilo-testing
description: >
  Protocolo de testes para o projeto Tannus/Chamilo.
  Carregue quando for criar ou executar testes PHPUnit, fixtures
  controladas, testes de integração ou dados de teste.
  Garante que dados de teste nunca contaminem dados reais.
---

# Testes — Tannus / Chamilo

## Princípio

Dados de teste são **temporários, marcados e removíveis**. Nunca devem contaminar dados reais ou ser confundidos com produção.

## Antes de Qualquer Teste com Banco

```bash
# Confirmar em qual banco estamos
php bin/console doctrine:schema:validate 2>&1 | head -5
echo "DB_URL: ${DATABASE_URL:-'NÃO DEFINIDA'}"

# Confirmar que é o banco de teste, não de produção
# APP_ENV deve ser 'test'
echo "APP_ENV: ${APP_ENV:-'NÃO DEFINIDA'}"
```

**Se APP_ENV não for 'test': PARE. Não execute fixtures nem testes que alteram banco.**

## PHPUnit — Configuração

```bash
# Rodar suite completa
php bin/phpunit

# Rodar arquivo específico
php bin/phpunit tests/Unit/NomeTest.php

# Rodar com coverage (só quando necessário — é lento)
php bin/phpunit --coverage-text

# Verificar configuração atual
cat phpunit.xml.dist 2>/dev/null || cat phpunit.xml
```

## Criação de Fixtures — Protocolo Obrigatório

1. **Pedir autorização** explícita antes de popular banco
2. **Definir escopo mínimo** — o mínimo necessário para o teste
3. **Registrar plano** em `DEVELOPMENT_LOG.md` antes de criar
4. **Naming obrigatório para dados de teste:**
   - Usuários: `test_[nome]` ou `testuser_[timestamp]`
   - Emails: `test+[sufixo]@tannus.dev`
   - Nomes: prefixo `[TEST]` — ex: `[TEST] Curso de Exemplo`
5. **Documentar remoção**: como e quando remover os dados de teste
6. **NUNCA** criar admin/superuser de teste sem flag explícita e documentação

## Tipos de Teste — Onde Criar

```
tests/
├── Unit/           # Testes de unidade — sem banco, sem HTTP
├── Integration/    # Testes com banco de dados de teste
├── Functional/     # Testes com HTTP client (WebTestCase)
└── fixtures/       # Fixtures YAML/PHP — sempre com dados marcados
```

## Template de Teste Unitário

```php
<?php
namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;

class NomeTest extends TestCase
{
    public function testDescricaoClara(): void
    {
        // Arrange
        $sut = new ClasseTestada();
        
        // Act
        $result = $sut->metodo($input);
        
        // Assert
        $this->assertEquals($expected, $result);
    }
}
```

## Template de Teste Funcional (Symfony WebTestCase)

```php
<?php
namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NomeControllerTest extends WebTestCase
{
    public function testRotaRetornaStatus200(): void
    {
        $client = static::createClient();
        $client->request('GET', '/rota-testada');
        
        $this->assertResponseIsSuccessful();
    }
}
```

## Limpeza Após Testes

```bash
# Remover fixtures após uso
php bin/console doctrine:fixtures:purge --env=test

# Verificar que dados de teste foram removidos
php bin/console dbal:run-sql "SELECT COUNT(*) FROM user WHERE username LIKE 'test_%'"
```

Registrar a limpeza no `DEVELOPMENT_LOG.md`.

## Testes de Regressão

Antes de qualquer PR, rodar ao menos os testes relacionados ao módulo alterado:
```bash
php bin/phpunit tests/ --filter NomeDoModulo
```

Se algum teste falhar: **não avançar**. Investigar, corrigir, re-testar.
