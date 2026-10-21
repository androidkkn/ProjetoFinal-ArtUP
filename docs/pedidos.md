# Cancelamento e exclusão de pedidos

## Cliente

Em Meus pedidos, o cliente pode solicitar o cancelamento dos próprios pedidos pendentes ou em produção, informando um motivo opcional de até 1000 caracteres. A solicitação fica em análise e não altera o status de produção do pedido. A página mostra a última solicitação e sua decisão.

Não é possível manter duas solicitações em análise para o mesmo pedido. Após uma recusa, o cliente pode enviar uma nova solicitação; as decisões anteriores permanecem no histórico. Pedidos concluídos, cancelados ou registros antigos de carrinho não aceitam solicitações.

## Administrador

Em Pedidos, o administrador pode aprovar ou recusar uma solicitação. Aprovar altera o pedido para cancelado; recusar mantém seu status. A decisão registra data e administrador. Alterar diretamente o status para cancelado aprova uma solicitação pendente; alterar para concluído encerra a solicitação como recusada.

Cada pedido também tem a opção Excluir pedido, que abre a confirmação de uma exclusão definitiva. A ação remove o pedido e suas solicitações. Produto e arte só são removidos quando não estão ligados a outro pedido nem ao carrinho. Referências antigas do carrinho ao pedido são limpas sem apagar os itens do carrinho.

Todas as alterações usam POST com token da sessão. A identidade do cliente e a permissão administrativa vêm da sessão, nunca de campos enviados pelo navegador. As operações usam transações e bloqueios para impedir inclusões duplicadas e alterações parciais.

## Banco

Execute `php database/migrar_cancelamentos.php` para criar a tabela de histórico e os índices e ajustar a referência legada do carrinho para `ON DELETE SET NULL`. A migração pode ser executada novamente e não exclui pedidos existentes.

