---
name: chamilo-replit-environment
description: >
  Configuração, limitações e boas práticas do ambiente Replit para o
  projeto Tannus/Chamilo. Carregue quando for configurar variáveis de
  ambiente, Secrets, processos, filesystem, build ou deploy no Replit.
  Inclui o que funciona e o que não funciona no ambiente.
---

# Ambiente Replit — Tannus / Chamilo

## Premissa Fundamental

Trate o Replit como ambiente de **desenvolvimento e demonstração** até decisão contrária documentada. Nunca assuma comportamento de produção sem validação.

## Variáveis de Ambiente e Secrets

### Regras
- Secrets sensíveis (DB_PASSWORD, APP_SECRET, JWT_SECRET, API_KEYS) → sempre em **Replit Secrets**, nunca em arquivos
- `.env` no repositório: apenas valores não-sensíveis e defaults de desenvolvimento
- `.env.example`: documenta todas as variáveis sem valores reais — manter sempre atualizado
- NUNCA commitar `.env` com valores reais
- NUNCA hardcodar credenciais no código

### Verificação de Variáveis
```bash
# Listar variáveis disponíveis no ambiente atual
printenv | grep -E "^(APP_|DB_|JWT_|MAILER_)" | sort

# Verificar se variável específica existe
echo ${NOME_VARIAVEL:-"NÃO DEFINIDA"}
```

Se uma variável não estiver definida: **pare e informe**. Nunca assuma o valor.

## Limitações do Replit

### Filesystem
- O filesystem pode ser **efêmero** entre reinicializações — não depender dele para dados persistentes
- Uploads de usuários: usar storage externo (S3, Cloudinary) se precisar de persistência real
- Logs: escrever em stdout/stderr, não em arquivos locais para persistência

### Processos
- Replit pode reiniciar o container — serviços devem ser idempotentes na inicialização
- Não depender de processos em background de longa duração sem supervisão

### Memória e CPU
- Ambientes Replit têm limites de memória — evitar operações intensivas sem necessidade
- `composer install` pode ser lento — planejar para builds longos
- Evitar `composer update` sem motivo forte (risco de conflito + lentidão)

### Banco de Dados
- Se usar banco embutido: considerar que dados podem ser perdidos
- Banco externo (PlanetScale, Supabase, Railway): preferível para dados persistentes
- Sempre verificar a string de conexão ativa antes de rodar migrations

## Comandos Seguros para Verificação do Ambiente

```bash
# Verificar PHP e extensões
php -v
php -m | grep -E "(pdo|curl|mbstring|zip|gd|intl)"

# Verificar Composer
composer --version

# Verificar Node/NPM (para build frontend)
node -v && npm -v

# Verificar conexão com banco
php bin/console doctrine:schema:validate 2>&1 | head -20

# Verificar estado do Symfony
php bin/console about
```

## Workflow de Inicialização no Replit

Ao iniciar uma nova sessão, sempre verificar:
1. `php bin/console about` — confirmar que o framework está operacional
2. `php bin/console doctrine:migrations:status` — ver estado do banco
3. Verificar se `.env.local` tem os Secrets necessários mapeados
4. Verificar se node_modules e vendor estão instalados

## Build Frontend no Replit

```bash
# Instalar dependências se necessário
npm ci

# Build de desenvolvimento
npm run dev

# Build de produção
npm run build

# NUNCA alterar arquivos em public/build diretamente — são gerados
```

## Arquivo replit.md

Manter `replit.md` na raiz com:
- Como iniciar o projeto
- Variáveis de ambiente necessárias (sem valores)
- Comandos de build e deploy
- Limitações conhecidas do ambiente
