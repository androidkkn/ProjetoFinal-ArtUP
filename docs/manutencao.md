# Revisão de manutenção — 21/10/2026

Objetivo: facilitar leitura e operação do projeto sem modificar comportamento funcional. Foram revisados os 30 arquivos PHP da aplicação, o CSS, as migrações, parâmetros SQL e documentação existente. Os arquivos de configuração visual e as imagens preparados anteriormente foram preservados. O conteúdo secreto de `database/credenciais.php` não foi copiado nem exibido; o formato de configuração foi confirmado por seus consumidores.

## Simplificação aplicada

A remoção de um item do carrinho e a exclusão administrativa de um pedido repetiam a mesma consulta:

```sql
DELETE FROM produto WHERE id = :produto
    AND NOT EXISTS (SELECT 1 FROM pedido WHERE id_produto = produto.id)
    AND NOT EXISTS (SELECT 1 FROM carrinho WHERE id_produto = produto.id)
```

Ela agora tem uma única definição em `SQL_EXCLUIR_PRODUTO_SEM_VINCULOS`, em [include/pedidos.php](../include/pedidos.php). Os chamadores continuam sendo `excluir_produto_carrinho()` em [include/functions_core.php](../include/functions_core.php) e `excluir_pedido_admin()` em [include/pedidos.php](../include/pedidos.php).

Isso evita divergência futura na condição que preserva produtos compartilhados. Não foram introduzidos wrappers de transação, novas conexões ou alterações nas consultas. Permanecem nos mesmos pontos:

- Validação de IDs e autorização.
- `prepare()`/`execute()` e parâmetro `produto`.
- Ordem das consultas, bloqueios, commit e rollback.
- Retornos booleanos e tratamento de exceções/logs.
- Remoção da arte pelo relacionamento já definido no banco.

`functions_core.php` já carrega `pedidos.php` antes da utilização das funções. A extração não exige novo include nem muda os contratos das funções existentes. A comparação de SQL normalizou apenas CRLF/LF dos arquivos; texto e parâmetros permaneceram equivalentes.

## Documentação

- Criado [README na raiz](../README.md) com requisitos, configuração real, execução, rotas, exemplos, estrutura, validação e manutenção.
- Transformado [docs/README.md](README.md) em índice para evitar duas versões das instruções de instalação.
- Separadas dependências confirmadas de características observadas em ambientes anteriores. Não há `.env`, Composer ou mbstring exigido por chamadas de produção identificadas.
- Registrada a ausência de baseline completa do banco, suíte atual, versão mínima formalmente suportada e configuração de produção.
- Preservados os relatórios históricos, sem apresentar suas contagens de testes como execução desta revisão.

## Verificação

Antes da alteração, os **30 arquivos PHP** passaram em `php -l` com PHP CLI 8.5.9. Depois da alteração, a mesma verificação foi repetida.

Foi utilizado um roteiro temporário fora do repositório, contra cópias anteriores e arquivos atuais. As **150 observações comparadas foram idênticas**:

| Grupo | Observações | O que foi comparado |
| --- | --- | --- |
| Exclusão no carrinho e pelo administrador | 34 | 17 cenários por operação: retorno, sequência SQL/parâmetros, estado da transação e texto dos logs. Inclui IDs inválidos, ausência de produto, papel inválido, resultado sem linhas e falhas simuladas nas etapas de banco. Etapas que não existem num dos fluxos equivalem à execução normal daquele fluxo. |
| Validações de dimensão, preço, inteiro, e-mail e senha | 95 | 19 entradas por função: strings, números, arrays, limites, valores não finitos e entradas inválidas. |
| Formatação monetária | 5 | Nulo, zero, valor decimal, casas excedentes e valor negativo. |
| Período das estatísticas | 6 | Padrões com data fixa, intervalo válido, data impossível, inversão, intervalo longo e array. |
| URLs | 5 | Raiz, páginas internas, serviços e instalação em subpasta. |
| Upload sem arquivo ou com erro | 5 | Resultado e mensagem por referência; não houve upload HTTP real. |

As operações de banco usaram objetos simulados, sem abrir conexão ou modificar dados. Essa comparação verifica a preservação das interações PHP; **não comprova integridade, concorrência, FKs ou execução SQL em PostgreSQL real**. As conversões de `NAN` produziram avisos preexistentes no PHP 8.5.9, observados antes e depois; o comportamento não foi corrigido nesta entrega.

Não existe suíte de testes no checkout. O roteiro temporário não foi adicionado como substituto da suíte removida. Não foram realizados testes HTTP, validação visual ou testes de carga nesta revisão. A lógica monetária e as permissões precisam continuar sendo exercitadas em testes de integração quando essa infraestrutura for retomada.

## Decisões adiadas para preservar comportamento

| Candidato | Por que não foi alterado |
| --- | --- |
| Remover conexão do catálogo ou mover guardas antes dos includes | Mudaria a precedência entre falha de banco e redirecionamento/autorização. |
| Centralizar todas as transações em uma função genérica | Pode mudar captura de exceções, ordem dos bloqueios, rollback e logs. O benefício não justifica esse risco nesta entrega. |
| Reescrever o cálculo do carrinho em lote | Pode mudar arredondamento, locks e falha parcial. Exige testes com PostgreSQL e volume representativo. |
| Renomear `cproduto`, `consulta_user` e outras funções/parâmetros | Os nomes fazem parte das chamadas atuais e podem ter consumidores externos, inclusive argumentos nomeados. Não há inventário que prove ausência desses consumidores. |
| Remover `dimensao_valida` por não encontrar chamador interno | Ausência de chamada no checkout não prova que a função pública está morta. |
| Aplicar tipos estritos, novos casts ou trocar `isset` por `empty` | Altera coerção, aceitação de valores e erros observáveis. |
| Arredondar no formatador monetário ou limitar dimensões de outra forma | Mudaria saídas conhecidas e a faixa de entradas aceitas. |
| Remover senhas legadas, endurecer senhas, cookies ou CSRF de autenticação | São mudanças funcionais de segurança; permanecem na auditoria/backlog, sem correção silenciosa. |
| Alterar fechamento PHP, saída dos includes ou mensagens | Pode afetar bytes enviados, headers e consumidores das mensagens. Não foi feita limpeza estética em massa. |
| Criar baseline SQL ou reexecutar seeds | Não há especificação completa confirmada; inventar o schema ou repetir INSERTs poderia mudar dados e constraints. |
| Integrar imagens ou completar a estilização | Mudaria a apresentação observável; continua como trabalho visual separado, já solicitado anteriormente. |

A principal limitação de confiança é a falta de testes de integração versionados. Esta revisão reduz uma duplicação concreta e documenta a operação existente; não declara correção dos achados de segurança nem aprovação para produção.
