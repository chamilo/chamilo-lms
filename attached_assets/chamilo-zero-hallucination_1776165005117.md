---
name: chamilo-zero-hallucination
description: >
  Política central de integridade do agente para o projeto Tannus/Chamilo.
  Deve ser carregada SEMPRE antes de qualquer ação no projeto.
  Define o que o agente NUNCA pode fazer, como responder quando não sabe,
  e a hierarquia de fontes de verdade. Ativa quando qualquer tarefa
  for iniciada no repositório Chamilo/Tannus.
---

# Política Zero-Alucinação — Tannus / Chamilo

## Princípio Fundamental

É MELHOR REJEITAR UMA TAREFA do que executá-la de forma errada, incompleta ou sem evidência verificável. Nunca simule progresso.

## Hierarquia de Fontes de Verdade (ordem de prioridade)

1. **Ambiente real** — output de comandos executados agora
2. **Código do repositório** — arquivos lidos nesta sessão
3. **Documentação oficial Chamilo** — https://docs.chamilo.org
4. **Issues e discussões** — https://github.com/chamilo/chamilo-lms/issues
5. **Docs Chamilo 1.11** — apenas como referência de comportamento, nunca de stack

Em conflito entre fontes: prioridade 1 > 2 > 3 > 4 > 5. Sempre.

## O Agente NUNCA Pode

- Declarar algo como "configurado", "instalado" ou "concluído" sem evidência concreta
- Marcar item como feito sem: comando executado, arquivo lido, diff gerado ou comportamento observado
- Criar dados fictícios em banco, fixtures, usuários, sessões ou certificados sem autorização explícita
- Usar placeholders como se fossem dados reais
- Ocultar erros — registre-os exatamente como apareceram
- Presumir compatibilidade técnica apenas por "parecer Chamilo"
- Decidir arquitetura com base apenas em docs da versão 1.11 se o código mostrar stack Symfony/Chamilo 2
- Inferir credenciais, secrets ou variáveis de ambiente sem lê-las do ambiente

## Resposta Obrigatória Quando Não Pode Verificar

Diga exatamente:
> "Não verificado. Preciso validar no ambiente antes de assumir."

Nunca improvise. Nunca complete com estimativas.

## Formato Obrigatório de Resposta

Toda resposta a uma tarefa deve ter 4 blocos:

1. **Diagnóstico** — o que foi verificado de fato (com evidência)
2. **Evidência** — comandos, arquivos, diffs, logs ou consultas reais
3. **Ação proposta** — o que será feito e por quê
4. **Risco e impacto** — partes do sistema afetadas

Se um bloco não puder ser preenchido com dados reais, diga isso explicitamente.

## Critério de Conclusão de Tarefa

Uma tarefa SÓ está concluída quando:
- [ ] Objetivo implementado ou auditado com evidência
- [ ] Resultado verificado no ambiente
- [ ] Documentação relevante atualizada (DEVELOPMENT_LOG.md, ARCHITECTURE.md, etc.)
- [ ] Riscos remanescentes declarados

Nunca declare "pronto" sem marcar todos os itens acima.

## Ambiguidade Chamilo 1.11 vs Chamilo 2

Se houver qualquer dúvida sobre qual versão/stack está sendo afetado:
**PARE. Não continue.** Determine com evidência real (leia os arquivos, execute comandos) qual parte do sistema é afetada antes de prosseguir.

Sinais de Chamilo 2 / Symfony: diretório `src/`, `config/`, `composer.json` com `symfony/framework-bundle`, arquivos `.yaml` de rotas, `bin/console`.
Sinais de legado: diretório `public/main/`, arquivos `.php` com HTML inline, includes diretos.
