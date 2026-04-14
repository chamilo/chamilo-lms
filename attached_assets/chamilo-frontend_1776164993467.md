---
name: chamilo-frontend
description: >
  Regras para desenvolvimento frontend no Tannus/Chamilo 2.
  Carregue antes de qualquer tarefa com Vue.js, Webpack Encore,
  assets, estilos CSS, templates Twig, ou qualquer alteração
  visual na interface. Distingue legado (public/main) de Symfony/Vue.
---

# Frontend — Tannus / Chamilo 2

## Identificação Obrigatória Antes de Qualquer Mudança

Antes de tocar em qualquer arquivo de UI, identifique:

```bash
# É legado ou Symfony/Vue?
# Legado: arquivos em public/main/ com PHP + HTML inline
# Symfony/Vue: templates Twig em templates/, componentes em assets/vue/

# Ver pipeline de build configurado
cat webpack.config.js 2>/dev/null || cat vite.config.js 2>/dev/null

# Ver ponto de entrada dos assets
ls assets/

# Ver o que está sendo compilado
cat package.json | grep -A5 '"scripts"'
```

**Se não identificar claramente qual sistema está afetado: pare e investigue antes de continuar.**

## Duas Camadas — Regras Distintas

### Camada Legada (`public/main/`)
- Código PHP com HTML inline
- CSS global em `public/css/`
- JavaScript vanilla ou jQuery
- **Alto risco**: alterações aqui podem afetar muitos cursos e usuários
- Regra: minimizar alterações; preferir override em theme quando possível

### Camada Symfony/Vue (`assets/`, `templates/`)
- Componentes Vue 3
- Templates Twig
- Webpack Encore para build
- **Zona mais segura** para novos desenvolvimentos

## Proibições Absolutas

- NUNCA alterar arquivos em `public/build/` diretamente — são gerados automaticamente
- NUNCA tratar `public/build/` como código-fonte
- NUNCA misturar ajuste visual com mudança estrutural sem documentar
- NUNCA commitar assets compilados sem commitar também o código-fonte que os gerou

## Workflow de Build

```bash
# Antes de alterar qualquer asset, confirmar que o build funciona
npm run dev 2>&1 | tail -20

# Após alterações, rebuildar
npm run dev    # desenvolvimento (com watch)
npm run build  # produção

# Verificar se o arquivo gerado existe
ls public/build/
```

## Vue.js — Padrões

- Composition API (Vue 3) — preferir sobre Options API
- Componentes em `assets/vue/components/`
- Props sempre tipadas com PropTypes ou TypeScript
- Emits declarados explicitamente
- Nunca manipular DOM diretamente — usar refs e reatividade do Vue

## Twig Templates — Padrões

- Usar blocos de herança (`{% extends %}`, `{% block %}`)
- Nunca colocar lógica de negócio em template — apenas apresentação
- Escapamento automático está ativo — nunca usar `|raw` sem sanitização prévia
- Tradução via `trans` filter — nunca strings hardcoded em português/inglês

## Verificação de Impacto Visual

Antes de fazer PR com mudanças de UI:
1. Testar em tela de 1280px (desktop)
2. Testar em tela de 375px (mobile)
3. Verificar modo claro e escuro se aplicável
4. Testar navegação por teclado nos elementos modificados
5. Verificar que não há regressão em outras páginas que usam o componente

## Documentação de Decisões

Registrar em `CUSTOMIZATIONS.md`:
- O que foi alterado vs. upstream
- Por que a alteração foi necessária
- Risco de conflito em updates futuros
- Estratégia adotada (override, plugin, patch)
