# Chamilo LMS 2.x — Arquitetura do Projeto

---
Version: 1.0
Last updated: 2026-04-14
Status: Active
Owner: Project maintainer

---

> ⚠️ Este projeto roda **Chamilo 2.x**, não Chamilo 1.11.x.
> As duas versões têm arquiteturas completamente diferentes.
> Qualquer guia ou tutorial para Chamilo 1.11 (PHP 7.4 + Apache + .htaccess) **não se aplica aqui**.

---

## Stack Tecnológica

| Componente | Versão | Função |
|---|---|---|
| PHP | 8.2 | Runtime backend |
| Symfony | 6.4 (LTS) | Framework MVC + DI + eventos |
| API Platform | 3.0 | REST/GraphQL API automática via atributos PHP |
| Doctrine ORM | 2.16 | Mapeamento objeto-relacional + migrações |
| Vue.js | 3 | Frontend SPA + componentes interativos |
| Webpack Encore | — | Bundler de assets (CSS, JS, Vue) |
| Tailwind CSS | — | Utilitários CSS |
| MySQL | 8.0 | Banco de dados (317 tabelas) |
| JWT | — | Autenticação de API (via LexikJWTBundle) |
| Yarn 4 | — | Gerenciador de pacotes JS |
| Composer | 2 | Gerenciador de pacotes PHP |

---

## Estrutura de Diretórios

| Diretório | Função | Política de modificação |
|---|---|---|
| `src/CoreBundle/` | Core da aplicação (entidades, controllers, eventos, fixtures) | ⚠️ Não modificar sem versionamento do diff |
| `src/CourseBundle/` | Bundle de cursos (entidades, controllers, repositórios) | ⚠️ Não modificar sem versionamento do diff |
| `src/LtiBundle/` | Integração LTI (Learning Tools Interoperability) | ⚠️ Não modificar sem versionamento do diff |
| `assets/` | Fontes JS/CSS/Vue (compiladas pelo Webpack Encore) | ✅ Customizar em subpastas próprias |
| `assets/vue/` | Componentes Vue.js 3 | ✅ Adicionar componentes custom aqui |
| `assets/css/` | Estilos globais e SCSS | ✅ Estender, não sobrescrever |
| `public/` | Document root do servidor web | — |
| `public/build/` | Assets compilados (gitignored, gerado em runtime) | 🚫 Não versionar |
| `public/main/` | Código legado PHP (legacy layer) | ⚠️ Minimizar alterações |
| `public/plugin/` | 56 plugins nativos do Chamilo | ✅ Zona segura para extensões |
| `config/` | Configuração Symfony (packages, routes, services) | ⚠️ Core protegido (chmod 0555) |
| `config/jwt/` | Chaves JWT (geradas em runtime) | 🔑 Writable — regenerado a cada fresh container |
| `var/` | Cache, logs, sessões (runtime) | 🚫 Não versionar |
| `vendor/` | Dependências Composer | 🚫 Nunca editar diretamente |
| `translations/` | Arquivos de internacionalização (i18n) | ✅ Adicionar idiomas custom |

---

## Banco de Dados

- **Motor**: MySQL 8.0
- **Charset**: utf8mb4 / utf8mb4_unicode_ci
- **Schema**: banco único (`chamilo`) com 317 tabelas
- **Prefixo de tabelas core**: `c_` (ex: `c_announcement`, `c_course`, `c_quiz`)
- **Migrações**: Doctrine Migrations (em `migrations/`) — nunca alterar tabelas manualmente
- **Fixtures**: `src/CoreBundle/DataFixtures/` — usadas para seed inicial

### Tabelas Core Confirmadas

| Tabela | Função |
|---|---|
| `access_url` | Instâncias multi-portal |
| `admin` | Usuários administradores |
| `user` | Usuários da plataforma |
| `course` | Cursos |
| `session` | Sessões de treinamento |
| `c_quiz` / `c_quiz_question` | Sistema de exercícios |

---

## Pontos de Extensão Seguros (sem conflito com upstream)

| Tipo | Localização | Exemplo |
|---|---|---|
| Plugin nativo | `public/plugin/[NomePlugin]/` | HelloWorld, CustomFooter |
| Componente Vue custom | `assets/vue/components/` | Novo widget de dashboard |
| Endpoint API custom | `src/CoreBundle/ApiResource/` | Recurso API Platform com atributo `#[ApiResource]` |
| Evento Symfony | `src/CoreBundle/EventSubscriber/` | Subscriber em eventos do Chamilo |
| Tradução custom | `translations/[locale]/` | Sobrescrever strings de idioma |
| Tema CSS | `assets/css/themes/` | Override de variáveis Tailwind/SCSS |

---

## Zonas de RISCO (evitar alterações diretas)

- `src/CoreBundle/Entity/` → entidades mapeadas pelo ORM; alterações exigem migration
- `src/CoreBundle/Controller/` → controllers core; preferir EventSubscribers para interceptar comportamento
- `vendor/` → **nunca editar** dependências diretamente
- `config/packages/` → protegido (chmod 0555); alterações exigem reinicialização
- `public/main/` → código legado PHP; minimizar mudanças para facilitar upgrades

---

## Infraestrutura Replit (estado atual)

### Servidor Web
- **Desenvolvimento**: `php -S 0.0.0.0:5000 -t public/` (PHP built-in server)
- **Porta externa**: 5000 → :80 (mapeada no `.replit`)
- ⚠️ O servidor built-in é single-thread — adequado para demo/dev, não para múltiplos usuários simultâneos

### Banco de Dados
- MySQL 8.0 rodando via socket em `/home/runner/mysql_run/mysql.sock`
- Symlink em `/run/mysqld/mysqld.sock` para compatibilidade
- ⚠️ **Filesystem efêmero no Cloud Run**: dados MySQL são perdidos a cada redeploy
- Para produção: migrar para banco externo (PlanetScale, Railway, AWS RDS, etc.)

### Configuração de Inicialização (`start.sh`)
1. Cria diretórios MySQL e inicializa data dir (se ausente)
2. Inicia `mysqld` com socket customizado
3. Cria symlink do socket para o caminho padrão do PHP
4. Cria banco e usuário `chamilo` se não existirem
5. Gera chaves JWT se ausentes
6. Aplica `chmod 0555` nos arquivos config sensíveis
7. Limpa cache Symfony
8. Inicia `php -S` com flags de runtime (memória, upload, timezone, socket)

### Deployment
- **Target**: Cloud Run (`deploymentTarget = "cloudrun"`)
- **Build**: `composer install --no-dev --optimize-autoloader && yarn build`
- **Run**: `bash start.sh`
- **Porta**: 5000 (interna) → 80 (externa)

---

## Fluxo de Request

```
Usuário → :80 (Replit proxy) → :5000 (php -S) → public/index.php (Symfony kernel)
                                                → public/legacy.php (código legado /main/)
                                                → public/main/*.php (pages legadas)
```

---

## PHP Extensions Instaladas

Todas as extensões obrigatórias para Chamilo 2.x estão presentes:
`curl`, `dom`, `fileinfo`, `gd`, `intl`, `json`, `mbstring`, `mysqli`,
`pdo_mysql`, `SimpleXML`, `xml`, `xmlreader`, `xmlwriter`, `zip`, `Zend OPcache`
