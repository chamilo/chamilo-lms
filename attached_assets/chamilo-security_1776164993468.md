---
name: chamilo-security
description: >
  Postura de segurança conservadora para o projeto Tannus/Chamilo.
  Carregue antes de qualquer tarefa envolvendo autenticação, JWT,
  permissões, uploads, inputs de usuário, rotas protegidas,
  secrets, integrações externas ou configurações de segurança.
---

# Segurança — Tannus / Chamilo

## Postura Padrão

**Conservadora.** Em caso de dúvida, escolha a opção mais restritiva e documente a decisão. Nunca relaxe segurança por conveniência.

## Áreas Sensíveis — Requerem Atenção Redobrada

Qualquer tarefa envolvendo os itens abaixo é considerada sensível:
- Autenticação e sessões
- JWT (geração, validação, rotação de chaves)
- Sistema de permissões e roles (Symfony Security, voters)
- Upload de arquivos
- HTML embutido renderizado para usuários
- Inputs de usuário (formulários, URLs, parâmetros)
- Integrações externas (APIs, webhooks)
- Configurações de CORS
- Rotas administrativas

## Proibições Absolutas

- Nunca expor secrets em logs, output de console ou mensagens de erro ao usuário
- Nunca deixar credenciais hardcoded no código
- Nunca manter instaladores acessíveis em produção (`web/install/`, `install.php`)
- Nunca assumir que input do usuário é confiável — sempre sanitizar e validar
- Nunca desativar CSRF protection sem justificativa documentada
- Nunca retornar stack traces completos ao usuário final
- Nunca usar `eval()`, `exec()` com input de usuário
- Nunca commitar `.env` com valores reais

## Verificações Obrigatórias Antes de Qualquer Mudança em Auth/Permissões

```bash
# Ver configuração atual de segurança
cat config/packages/security.yaml

# Listar voters existentes
find src/ -name "*Voter*" -type f

# Listar controllers com autenticação
grep -r "@IsGranted\|#\[IsGranted\]\|denyAccessUnlessGranted" src/ --include="*.php" -l

# Ver configuração de firewall
php bin/console debug:firewall
```

## Validação de Inputs

Sempre usar Symfony Validator — nunca validação manual:
```php
// ✓ Correto
$errors = $this->validator->validate($entity);

// ✗ Errado
if (strlen($input) > 0) { ... }
```

Para HTML de usuário: sempre usar HTMLPurifier ou escapamento adequado.
Para uploads: verificar MIME type real (não apenas extensão), limitar tamanho, usar diretório fora do webroot.

## Uploads Seguros — Checklist

- [ ] Verificar MIME type real com `finfo_file()`, não confiar na extensão
- [ ] Limitar tamanho máximo no PHP e no servidor web
- [ ] Gerar nome de arquivo aleatório — nunca usar nome original do usuário
- [ ] Salvar fora do webroot ou com acesso controlado
- [ ] Verificar se o arquivo é executável (negar PHP, shell scripts)

## JWT — Regras

- Chave secreta sempre em Replit Secrets, nunca em arquivo
- Tempo de expiração curto para access token (15-60 min)
- Refresh token com rotação — invalidar após uso
- Nunca armazenar dados sensíveis no payload do JWT
- Validar `iss`, `aud` e `exp` em toda verificação

## Reporte de Vulnerabilidades

Seguir política oficial do Chamilo: https://github.com/chamilo/chamilo-lms/security/policy
Para vulnerabilidades encontradas no projeto Tannus: registrar em `DEVELOPMENT_LOG.md` como `[SECURITY]` e tratar com prioridade máxima antes de qualquer outro desenvolvimento.

## Auditoria de Segurança — Comandos

```bash
# Verificar dependências com vulnerabilidades conhecidas
composer audit

# Verificar configuração do Symfony em produção
php bin/console security:check 2>/dev/null || echo "comando não disponível"

# Listar rotas públicas (sem firewall)
php bin/console debug:router --show-controllers | grep -v "authenticated"
```
