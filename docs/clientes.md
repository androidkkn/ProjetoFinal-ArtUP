# Consulta administrativa de clientes

A opção **Administração → Clientes** abre [admin/clientes.php](../admin/clientes.php). A tela lista todos os usuários cadastrados, incluindo administradores e contas sem pedidos. Não exibe senhas nem hashes. Os dados de cadastro existentes são ID, nome, e-mail e perfil; telefone, endereço e data de cadastro não fazem parte das colunas utilizadas pelo projeto.

## Listagem e busca

- Busca por trecho do nome ou e-mail, sem distinguir maiúsculas/minúsculas, ou por ID.
- `%`, `_` e `!` são tratados como texto literal. Os valores são parâmetros da consulta.
- Busca de até 255 caracteres UTF-8; arrays, IDs/páginas inválidos e entradas com byte nulo são recusados com HTTP 422.
- 25 usuários por página, ordenados por ID decrescente. A busca é preservada nos links.
- Cada linha mostra perfil, quantidade de pedidos, pedidos em aberto, valor não cancelado e último pedido. **Ver detalhes** abre o histórico do usuário.

Exemplos: `admin/clientes.php?pesquisa=ana`, `admin/clientes.php?pagina=2` e `admin/clientes.php?id=1`.

## Detalhes do usuário

A ficha mostra nome, e-mail, perfil e último pedido, seguidos dos indicadores:

| Indicador | Definição |
| --- | --- |
| Pedidos registrados | Registros do usuário em `pedido`, excluindo o status legado `carrinho`; inclui cancelados. |
| Em aberto | Pedidos `pendente` ou `em producao`. |
| Entregues / cancelados | Contagens de `concluido` (rótulo Entregue) e `cancelado`, respectivamente; Retirada não é contado como Entregue. |
| Valor não cancelado | Soma de `pedido.valor_total` nos pedidos cujo status não é `cancelado`. |
| Ticket médio | Média dos valores preenchidos dos pedidos não cancelados, arredondada para duas casas no PostgreSQL. |
| Itens no carrinho | Quantidade de linhas do usuário em `carrinho`; não é soma de unidades nem pedido confirmado. |
| Cancelamentos em análise | Pedidos com solicitação pendente, sem multiplicar a contagem pelo histórico de respostas. |

O último pedido considera a criação de pedidos confirmados, inclusive cancelados. Valores nulos entram na contagem de pedidos, mas não nas somas/médias; há um aviso quando eles existem. Uma conta sem pedidos tem indicadores zerados e histórico vazio.

O histórico exibe até 25 pedidos por página, com data e ID decrescentes, incluindo quantidade, status, produto/tipo, preço unitário, total, medidas em cm, formato/espessura, arte e última situação de cancelamento. A ação **Gerenciar** abre a busca pelo pedido na gestão existente; o link da arte reutiliza a autorização de download do administrador.

`pagina_pedidos` controla as páginas do histórico e `pagina` preserva o retorno à listagem de clientes. Por exemplo: `admin/clientes.php?id=1&pagina_pedidos=2`. Páginas além da última são ajustadas para a última disponível.

Os valores representam pedidos, não pagamentos recebidos. A aplicação não registra recebimento ou lucro. Pedidos excluídos definitivamente deixam de aparecer; itens de carrinho ficam fora das métricas de pedidos.

## Acesso e implementação

A guarda administrativa é executada antes de abrir a conexão. Visitantes são redirecionados ao login; clientes e papéis administrativos sem o booleano `true` recebem HTTP 403. As funções em [include/clientes.php](../include/clientes.php) também verificam autorização. A página é somente de consulta, aceita GET/HEAD e recusa outros métodos com HTTP 405. Ela não permite editar contas ou suas permissões.

As respostas administrativas usam `Cache-Control: private, no-store`, `X-Content-Type-Options: nosniff` e `X-Frame-Options: DENY`. As saídas dinâmicas são escapadas, inclusive nome, e-mail, pesquisa, categoria, nome da arte e status desconhecidos.

Não há migração nova: a funcionalidade usa as tabelas existentes de usuários, produtos, pedidos, carrinho, artes e solicitações de cancelamento. Cada modo utiliza duas consultas, sem consulta por usuário/pedido dentro de laços PHP. A seleção das contas é limitada antes das agregações; pedidos e carrinho são agregados separadamente para evitar multiplicação de valores. Valores monetários permanecem como `NUMERIC` no banco e texto no PHP.

A paginação numerada usa LIMIT/OFFSET com ordenação estável. Para históricos muito profundos, avaliar paginação por cursor e índices com planos reais, conforme o volume; esta entrega não cria índices nem altera a estrutura persistente.

## Verificação

- **40 verificações de integração aprovadas** no PostgreSQL, exclusivamente em tabelas temporárias sob `search_path=pg_temp` e transação revertida. Cobrem contas sem pedidos, administradores, precisão decimal, cancelados, valores nulos, carrinhos e históricos sem duplicação, busca literal, Unicode, tentativa de injeção, paginação e autorização.
- **55 verificações HTTP/navegador aprovadas**, com o mesmo isolamento: listagem e detalhes em 320/390/768/1440 px; estados vazios; HTML e links; páginas inválidas/inexistentes; erros de consulta; acesso indevido; somente leitura; cabeçalhos privados; escape de dados maliciosos; navegação entre páginas e instalação em subpasta.
- **32 arquivos PHP aprovados no lint.** Capturas de listagem em desktop e detalhes no celular foram inspecionadas.

Nenhum dado persistente de cliente, pedido ou arte foi alterado pelos testes. As ferramentas temporárias ficaram fora do repositório; a suíte anteriormente removida não foi recriada. Os riscos de sessão/revogação já registrados na auditoria continuam sendo questões comuns da aplicação.
