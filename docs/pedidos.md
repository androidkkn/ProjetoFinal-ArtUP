# Cancelamento e exclusão de pedidos

## Status dos pedidos

O painel usa a lista compartilhada `STATUS_PEDIDOS`: Pendente (`pendente`), Em produção (`em producao`), Retirada (`retirada`), Entregue (`concluido`) e Cancelado (`cancelado`). O nome anterior Concluído foi substituído por Entregue na interface; a chave `concluido` foi mantida para preservar os registros existentes, consultas e regras. Retirada é um status separado, aceito no formulário e pela validação do backend. Não exige migração de dados no schema utilizado pelo projeto.

As regras de cancelamento continuam permitindo solicitações somente em Pendente ou Em produção; a inclusão de Retirada não amplia essa lista. Os indicadores de entregues continuam contando a chave `concluido`, sem incluir Retirada. Os valores de Retirada entram no total não cancelado, como os demais status não cancelados.

## Cliente

Em Meus pedidos, o cliente pode solicitar o cancelamento dos próprios pedidos pendentes ou em produção, informando um motivo opcional de até 1000 caracteres. A solicitação fica em análise e não altera o status de produção do pedido. A página mostra a última solicitação e sua decisão.

Não é possível manter duas solicitações em análise para o mesmo pedido. Após uma recusa, o cliente pode enviar uma nova solicitação; as decisões anteriores permanecem no histórico. Pedidos em Retirada, entregues, cancelados ou registros antigos de carrinho não aceitam solicitações.

## Administrador

Em Pedidos, o administrador pode aprovar ou recusar uma solicitação. Aprovar altera o pedido para cancelado; recusar mantém seu status. A decisão registra data e administrador. Alterar diretamente o status para cancelado aprova uma solicitação pendente; alterar para Entregue (chave `concluido`) encerra a solicitação como recusada.

Cada pedido também tem a opção Excluir pedido, que abre a confirmação de uma exclusão definitiva. A ação remove o pedido e suas solicitações. Produto e arte só são removidos quando não estão ligados a outro pedido nem ao carrinho. Referências antigas do carrinho ao pedido são limpas sem apagar os itens do carrinho.

Todas as alterações usam POST com token da sessão. A identidade do cliente e a permissão administrativa vêm da sessão, nunca de campos enviados pelo navegador. As operações usam transações e bloqueios para impedir inclusões duplicadas e alterações parciais.

## Banco

Execute `php database/migrar_cancelamentos.php` para criar a tabela de histórico e os índices e ajustar a referência legada do carrinho para `ON DELETE SET NULL`. A migração pode ser executada novamente e não exclui pedidos existentes.

