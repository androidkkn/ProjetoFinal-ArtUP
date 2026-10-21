# Estatísticas administrativas

A página [admin/estatisticas.php](../admin/estatisticas.php) substitui o conteúdo provisório por um painel
de pedidos. O acesso é pelo menu **Administração → Estatísticas** e exige sessão administrativa, usando
a mesma guarda das demais páginas de administração. Visitantes são encaminhados ao login e clientes
recebem HTTP 403. A resposta autenticada usa `Cache-Control: private, no-store`.

## Filtro e leitura do período

- Sem filtros, o período vai do primeiro dia do mês atual até hoje, considerando `America/Sao_Paulo`
  para determinar essas datas padrão.
- `inicio` e `fim` são parâmetros GET no formato `AAAA-MM-DD`. As duas datas são incluídas: o limite
  superior da consulta é o começo do dia seguinte à data final.
- São aceitos intervalos de até cinco anos, inclusive consultas de um único dia. Datas impossíveis,
  parâmetros em array, início posterior ao fim e intervalos maiores retornam HTTP 422, sem consultar as métricas.
- O botão **Mês atual** remove os parâmetros e reaplica o período padrão. O filtro funciona sem JavaScript.

Exemplo: `admin/estatisticas.php?inicio=2026-01-01&fim=2026-12-31`.

A referência temporal é `pedido.data`, a data de criação do pedido. A coluna atual é timestamp sem fuso:
o filtro compara o horário como ele está registrado no banco, sem reinterpretar ou converter registros antigos.
Status, preços e solicitações refletem a situação **atual** dos pedidos desse período; não são uma
reconstituição do estado que existia no último dia selecionado.

## Definição dos indicadores

| Indicador | Regra |
| --- | --- |
| Pedidos confirmados | Quantidade de registros em `pedido` no período, excluindo o status legado `carrinho`. Inclui cancelados. |
| Em aberto | Pedidos com status `pendente` ou `em producao`. |
| Valor não cancelado | Soma de `pedido.valor_total` dos pedidos cujo status é diferente de `cancelado`. |
| Ticket médio | Média dos valores totais preenchidos dos pedidos não cancelados, arredondada para duas casas. |
| Clientes com pedidos | Quantidade de IDs de clientes distintos entre os pedidos do período, incluindo cancelados. Não é o total de contas cadastradas. |
| Cancelamentos em análise | Quantidade de pedidos do período com solicitação `pendente`. Uma solicitação de um pedido criado fora do filtro não entra neste indicador. |
| Valores entregues/cancelados | Somas dos valores em `concluido` (rótulo Entregue) e `cancelado`. O valor entregue já está dentro do valor não cancelado. Retirada é exibido como status próprio e não entra no valor entregue. |

Cada registro de `pedido` representa **um item confirmado**. A confirmação de um carrinho com três
itens gera três pedidos, não um agrupamento único de compra. Quantidades de unidades são somadas
separadamente na tabela por categoria.

O projeto não registra pagamento, recebimento, custo de produção ou margem. Por isso, o painel não
chama esses totais de receita recebida, lucro ou faturamento pago. Ele utiliza os preços já gravados em
`pedido`, sem recalcular tarifas, dimensões em centímetros ou multiplicar novamente pela quantidade.

## Gráficos e tabelas

- **Pedidos por status:** contagem e valor por situação atual. Os cinco status conhecidos aparecem
  mesmo sem ocorrências; status antigos diferentes desses também são exibidos, escapados como texto.
- **Evolução mensal:** pedidos e cancelados pela data de criação, com o valor não cancelado. Meses sem
  movimento são preenchidos com zero. No primeiro e último mês, só entram as datas efetivamente selecionadas.
- **Pedidos por categoria:** quantidade de pedidos, unidades não canceladas e valor não cancelado,
  ordenados pelo maior valor. Categorias só aparecem quando têm pedidos no período.

Os gráficos utilizam barras HTML nativas (`meter`) com rótulos e números textuais; tabelas têm cabeçalhos
e legendas. A página tem layout responsivo, navegação por teclado, link para pular ao conteúdo, rolagem
horizontal nas tabelas em telas pequenas e estilos para impressão. Não depende de CDN ou biblioteca de gráficos.

## Dados incompletos e exceções

Pedidos com `valor_total = NULL` entram nas contagens, mas são excluídos das somas e médias monetárias.
O painel mostra quantos estão sem valor, evitando confundir ausência de preço com preço igual a zero.
Se não houver pedidos, apresenta os indicadores zerados e uma mensagem para ajustar as datas.

Ao aprovar um cancelamento, o pedido passa a sair dos valores não cancelados. Ao recusar a solicitação,
o status de produção é preservado. Pedidos excluídos definitivamente deixam de aparecer; sem uma tabela
de eventos/histórico analítico não há como continuar contando registros removidos.

Erros de consulta retornam HTTP 500 com mensagem genérica. Detalhes da exceção não são exibidos ao
administrador; o log registra o código. Os demais achados de sessão/autorização da auditoria continuam
no [product backlog](product-backlog.md), incluindo a revogação de papel em sessões existentes.

## Implementação e banco

- [include/estatisticas.php](../include/estatisticas.php): validação de datas e consulta agregada, com
  parâmetros preparados e verificação de sessão administrativa também na função de consulta.
- [admin/estatisticas.php](../admin/estatisticas.php): controle de acesso, filtros, indicadores e renderização.
- [admin/estatisticas.css](../admin/estatisticas.css): componentes específicos do painel; fundamentos, cabeçalho e rodapé usam [css/estilo.css](../css/estilo.css) e o template compartilhado.

Uma única instrução SQL calcula resumo, status, categorias e meses no mesmo snapshot, sem executar
consultas dentro dos loops de apresentação ou carregar os pedidos completos no PHP. Os valores monetários
permanecem como `NUMERIC` no PostgreSQL e texto no retorno JSON, preservando os centavos sem conversão em float.

Não há migração nova nem alterações dos dados existentes. São necessárias as tabelas base e a tabela de
solicitações da [migração de cancelamentos](../database/migrations/002_cancelamentos.sql). Índices adicionais
para crescimento do volume estão previstos no backlog; o filtro de datas não aplica funções a `pedido.data`,
permitindo aproveitar um índice adequado quando ele for criado.

## Verificação desta entrega

A lógica foi verificada com 26 condições executadas diretamente contra tabelas temporárias: período padrão,
datas inválidas/bissextas, ausência de pedidos, limites das datas, estados e clientes, somas decimais,
ticket, valores ausentes, histórico de solicitações sem duplicação, meses zerados, categorias e autorização.
Nenhuma tabela persistente foi alterada nessas verificações. A suíte anteriormente removida não foi recriada.

Também passaram 18 verificações HTTP com servidor local temporário, cobrindo visitante/cliente/administrador,
papel administrativo inválido, formulário, gráficos, navegação atual, cache privado, estado vazio, filtros
inválidos, escape de texto vindo do banco, instalação em subpasta e carregamento do CSS. O navegador
automatizado não conseguiu gerar capturas por restrição de GPU/permissões nesta estação; não é declarada
aprovação visual automatizada. Os arquivos e processos temporários da verificação foram encerrados/removidos.
