# Funcionalidades e regras do ArtUP

Guia consolidado dos fluxos do cliente e do administrador. Instalação, requisitos e execução estão no [README principal](../README.md). Os resultados de verificação nas seções abaixo pertencem às respectivas entregas; não indicam uma nova execução nem uma suíte versionada.

- [Navegação e acesso](#navegacao)
- [Cálculo dos preços](#precos)
- [Envio e download das artes](#artes)
- [Status, cancelamento e exclusão de pedidos](#pedidos)
- [Consulta administrativa de clientes](#clientes)
- [Estatísticas administrativas](#estatisticas)

<a id="navegacao"></a>

## Navegação e acesso

O cabeçalho compartilhado mostra Início, Catálogo, Meus pedidos, Carrinho e Sair para usuários autenticados. Administradores também veem Administração e os links Estatísticas, Pedidos, Clientes e Produtos em todas as páginas que usam esse cabeçalho. Visitantes veem Início, Entrar e Cadastrar.

Na área administrativa, a página atual é identificada com `aria-current="page"`. Estatísticas abre o
[painel de pedidos](funcionalidades.md#estatisticas), com filtros de período e indicadores; os atalhos do painel levam
à gestão de pedidos e à análise das solicitações de cancelamento.

**Clientes** abre a [consulta de usuários](funcionalidades.md#clientes), com busca, indicadores e histórico individual de pedidos. O acesso permanece restrito ao administrador, inclusive ao abrir diretamente o endereço da página.

`include/navegacao.php` monta os caminhos a partir da página acessada. Os links para início, catálogo, serviços, administração, login e logotipo funcionam na raiz do servidor e em uma subpasta, inclusive nas páginas de `app/servicos`.

Sair envia um formulário POST com token da sessão para `login/logout.php`. O servidor valida o token, remove os dados da sessão, apaga o cookie e redireciona para o login. Abrir a URL por GET não encerra a sessão. A proteção das páginas administrativas continua sendo feita no servidor, independentemente da visibilidade do menu.

<a id="precos"></a>

## Cálculo dos preços

Os quatro serviços usam `include/form_produto.php` para mostrar uma prévia antes de adicionar ao carrinho. Os valores são calculados no servidor a partir de `tipos_de_produto.valor_base` e `precodimensoes.valormetro`.

### Regras

- **Adesivos e banners:** comprimento e largura em **centímetros (cm)**. Área em m² = comprimento × largura ÷ 10.000. Preço unitário = área em m² × preço por m² da categoria + valor base do tipo/acabamento.
- **Placas:** comprimento e largura em **centímetros (cm)**. Preço unitário = (comprimento × largura ÷ 10.000) × preço por m². Não há tipo nem adicional por espessura cadastrado; 1, 2 e 3 mm usam a mesma tarifa.
- **Papelaria:** o valor base do tipo é o preço de uma folha **A4**. Multiplicadores: A1 = 8; A2 = 4; A3 = 2; A4 = 1; A5 = 0,5; A6 = 0,25; A7 = 0,125; A8 = 0,0625. O preço por m² da categoria não entra nesse cálculo.
- O preço unitário é arredondado para duas casas decimais, com meio centavo arredondado para cima. O total é esse preço unitário × quantidade. Os cálculos usam `NUMERIC` do PostgreSQL para preservar os centavos.
- Quantidades devem ser inteiros positivos; dimensões, números positivos finitos. Preço unitário e total devem caber em `NUMERIC(12,2)`, até R$ 9.999.999.999,99. Tipos de outra categoria, tarifas ausentes ou negativas e dados inválidos impedem a inclusão.

Exemplos com os parâmetros iniciais: adesivo BOPP de 200 × 300 cm tem área de 6 m², custa R$ 5,60 por unidade e R$ 16,80 para 3 unidades; sulfite A3 = R$ 1,00 por folha; sulfite A8 = R$ 0,03 por folha após o arredondamento.

### Unidades e compatibilidade com dados existentes

Os formulários, a prévia, o carrinho, os pedidos e a edição administrativa trabalham com centímetros.
A tarifa administrativa continua em R$/m², evitando a perda de precisão que ocorreria ao dividir tarifas
pequenas por 10.000 e salvá-las numa coluna monetária de duas casas.

As colunas `produto.largura` e `produto.comprimento` continuam armazenadas em **metros**, preservando os
registros existentes. O servidor divide a entrada em centímetros por 100 ao salvar e multiplica as
medidas por 100 ao retorná-las nas funções de listagem. A prévia converte as duas dimensões com `NUMERIC`
antes de calcular a área, sem arredondamento monetário intermediário. A edição administrativa e o cálculo
de carrinhos antigos usam a mesma convenção, evitando conversão duplicada.

Não há migração de dados nem recálculo automático de preços já aceitos. Formulários abertos antes da
mudança precisam ser recarregados: o campo oculto `unidade_medidas=cm` impede interpretar valores de
formulários antigos em metros como se já fossem centímetros. A espessura das placas permanece em mm
e os formatos de papel A1–A8 mantêm os fatores existentes.

### Carrinho e pedidos

O usuário calcula a prévia e confirma a inclusão. Se os dados ou o preço mudarem, deve conferir a nova prévia. A inclusão recalcula e valida os valores; nenhum preço enviado pelo navegador é aceito como fonte do cálculo.

O carrinho armazena preço unitário e total. A confirmação preserva esses valores no pedido, mesmo que o administrador tenha alterado as tarifas depois. Itens antigos sem preço podem ser calculados pelo botão no carrinho e precisam ter valores antes da confirmação. Esse botão preenche somente os itens sem preço; não altera os já calculados.

Alterar apenas a tabela de tarifas não muda pedidos anteriores. Editar explicitamente quantidade, medidas ou tamanho de um item na administração recalcula o preço com a tabela atual, salvando dados e valores na mesma transação.

Não é necessária migração no banco atual: `carrinho` e `pedido` já possuem `preco_unitario` e `valor_total`.

<a id="artes"></a>

## Envio e download das artes

Os quatro serviços aceitam uma arte opcional por produto em PNG, JPG/JPEG, WEBP ou PDF. A validação confere o upload HTTP, o tamanho real, o MIME detectado pelo servidor e a extensão. Imagens são decodificadas com GD para rejeitar arquivos corrompidos, sem regravar ou reduzir a qualidade do original. PDFs passam por uma checagem básica do cabeçalho e do marcador final `%%EOF`; essa checagem não substitui a validação completa do documento para impressão. Arquivos vazios, truncados, extensões incompatíveis e tipos não permitidos são recusados.

O limite da aplicação é 10 MB. O formulário usa o menor limite entre a aplicação, `upload_max_filesize` e `post_max_size` do PHP, reservando espaço para os outros campos. Na configuração atual, o limite efetivo é **2 MB**. Para permitir 10 MB em outra instalação, configure `upload_max_filesize=10M` e `post_max_size=12M` no PHP do servidor.

Imagens têm limite de 40 megapixels e passam por uma verificação de memória disponível antes da decodificação. Se a resolução exceder esses limites, o formulário pede uma versão menor ou PDF. O servidor precisa das extensões Fileinfo e GD, com suporte aos formatos aceitos.

### Armazenamento e vínculo

As artes ficam na tabela `artes` do PostgreSQL, com conteúdo binário `BYTEA`, nome original, MIME, tamanho, usuário e produto. Não existem URLs públicas para os arquivos. Os backups do banco devem incluir essa tabela.

O primeiro envio guarda um rascunho associado à sessão e à categoria. Ele é preservado ao calcular novamente a prévia, sem exigir que o usuário selecione o arquivo outra vez. Enviar uma nova arte substitui o rascunho anterior; uma substituição inválida preserva o arquivo anterior. O botão Remover arte permite descartar o rascunho antes de adicionar o produto.

Cada formulário informa qual rascunho foi exibido. Se outra aba substituir/remover a arte, ou o rascunho expirar, o formulário antigo bloqueia a inclusão e pede nova conferência. Assim, uma aba não inclui silenciosamente a arte escolhida por outra. As abas do mesmo serviço ainda compartilham um rascunho, com esse bloqueio de conflitos.

Ao adicionar o produto ao carrinho, o vínculo com a arte é salvo na mesma transação do produto e dos preços. Uma falha mantém o rascunho e não cria um produto parcial.

Rascunhos expiram após 24 horas e são removidos quando um novo arquivo é recebido. Artes vinculadas a produtos não expiram. Confirmar pedidos preserva o vínculo; excluir um item descartável do carrinho exclui seu produto e sua arte por `ON DELETE CASCADE`.

### Acesso

O carrinho, Meus pedidos e a administração de pedidos mostram o nome da arte e um link de download em `app/arte.php?produto=ID`. O cliente só pode baixar artes dos próprios produtos em carrinho ou pedidos; administradores autenticados podem baixar as artes dos produtos. Usuários sem acesso recebem 404, e visitantes sem login são redirecionados para o login.

Os arquivos são enviados como anexos, com tipo MIME validado, `nosniff` e cache desativado. Nomes são escapados no HTML e codificados no cabeçalho de download.

### Instalação

A tabela de artes e seu índice já estão no [dump SQL](../database/dump_banco.sql). Para uma instalação nova, importe o dump em banco vazio conforme o [README](../README.md#instalação-e-configuração).

<a id="pedidos"></a>

## Status, cancelamento e exclusão de pedidos

### Status dos pedidos

O painel usa a lista compartilhada `STATUS_PEDIDOS`: Pendente (`pendente`), Em produção (`em producao`), Retirada (`retirada`), Entregue (`concluido`) e Cancelado (`cancelado`). O nome anterior Concluído foi substituído por Entregue na interface; a chave `concluido` foi mantida para preservar os registros existentes, consultas e regras. Retirada é um status separado, aceito no formulário e pela validação do backend. Não exige migração de dados no schema utilizado pelo projeto.

As regras de cancelamento continuam permitindo solicitações somente em Pendente ou Em produção; a inclusão de Retirada não amplia essa lista. Os indicadores de entregues continuam contando a chave `concluido`, sem incluir Retirada. Os valores de Retirada entram no total não cancelado, como os demais status não cancelados.

### Cliente

Em Meus pedidos, o cliente pode solicitar o cancelamento dos próprios pedidos pendentes ou em produção, informando um motivo opcional de até 1000 caracteres. A solicitação fica em análise e não altera o status de produção do pedido. A página mostra a última solicitação e sua decisão.

Não é possível manter duas solicitações em análise para o mesmo pedido. Após uma recusa, o cliente pode enviar uma nova solicitação; as decisões anteriores permanecem no histórico. Pedidos em Retirada, entregues, cancelados ou registros antigos de carrinho não aceitam solicitações.

### Administrador

Em Pedidos, o administrador pode aprovar ou recusar uma solicitação. Aprovar altera o pedido para cancelado; recusar mantém seu status. A decisão registra data e administrador. Alterar diretamente o status para cancelado aprova uma solicitação pendente; alterar para Entregue (chave `concluido`) encerra a solicitação como recusada.

Cada pedido também tem a opção Excluir pedido, que abre a confirmação de uma exclusão definitiva. A ação remove o pedido e suas solicitações. Produto e arte só são removidos quando não estão ligados a outro pedido nem ao carrinho. Referências antigas do carrinho ao pedido são limpas sem apagar os itens do carrinho.

Todas as alterações usam POST com token da sessão. A identidade do cliente e a permissão administrativa vêm da sessão, nunca de campos enviados pelo navegador. As operações usam transações e bloqueios para impedir inclusões duplicadas e alterações parciais.

### Banco

O [dump SQL](../database/dump_banco.sql) já inclui o histórico de cancelamentos, seus índices e a referência legada `carrinho.id_pedido` com `ON DELETE SET NULL`. A instalação usa o dump; os scripts antigos de migração foram removidos.

<a id="clientes"></a>

## Gestão administrativa de clientes

A opção **Administração → Clientes** abre [admin/clientes.php](../admin/clientes.php). A tela lista todos os usuários cadastrados, incluindo administradores e contas sem pedidos. Não exibe senhas nem hashes. Os dados de cadastro existentes são ID, nome, e-mail e perfil; telefone, endereço e data de cadastro não fazem parte das colunas utilizadas pelo projeto.

### Listagem e busca

- Busca por trecho do nome ou e-mail, sem distinguir maiúsculas/minúsculas, ou por ID.
- `%`, `_` e `!` são tratados como texto literal. Os valores são parâmetros da consulta.
- Busca de até 255 caracteres UTF-8; arrays, IDs/páginas inválidos e entradas com byte nulo são recusados com HTTP 422.
- 25 usuários por página, ordenados por ID decrescente. A busca é preservada nos links.
- Cada linha mostra perfil, quantidade de pedidos, pedidos em aberto, valor não cancelado e último pedido. **Ver detalhes** abre o histórico do usuário.

Exemplos: `admin/clientes.php?pesquisa=ana`, `admin/clientes.php?pagina=2` e `admin/clientes.php?id=1`.

### Alteração do perfil de acesso

Na listagem e na ficha do usuário, o administrador escolhe **Cliente** (`admin=false`) ou **Administrador** (`admin=true`) e clica em **Salvar perfil**. O seletor aparece em todas as contas, inclusive na própria. A alteração preserva nome, e-mail, senha e pedidos. Após salvar o perfil de outra conta, a página mantém a busca e a paginação e mostra uma mensagem de confirmação.

O administrador pode mudar o próprio perfil para Cliente quando houver outro administrador. Ao fazer isso, a sessão perde o acesso administrativo imediatamente e retorna à página inicial com uma confirmação. A última conta administrativa não pode ser rebaixada: a opção Cliente fica desabilitada e a tela orienta a promover outra conta primeiro. Essa regra também é verificada no servidor. O salvamento usa POST com token CSRF da sessão e aceita somente os valores `true` e `false`. IDs ou perfis inválidos e tentativas de rebaixar o último administrador retornam HTTP 422, usuários inexistentes retornam 404 e envios sem autorização ou token válido retornam 403.

O perfil de quem faz a alteração é conferido novamente no banco durante uma transação curta. Os administradores ativos e as contas envolvidas são bloqueados por ID em ordem crescente, preservando pelo menos um administrador mesmo em alterações simultâneas. A cada requisição autenticada, o perfil é relido do banco: promoções e remoções de acesso por outro administrador valem na próxima requisição, sem exigir novo login. Se a conta tiver sido excluída, a sessão perde a autenticação.

### Detalhes do usuário

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

### Acesso e implementação

A guarda de autenticação redireciona visitantes ao login antes de consultar o perfil. Para contas autenticadas, o perfil é atualizado pelo banco antes de verificar o acesso administrativo; clientes recebem HTTP 403. Se não for possível verificar o perfil, a requisição é interrompida com HTTP 500. As funções em [include/clientes.php](../include/clientes.php) também verificam autorização. A página aceita GET/HEAD para consultas e POST para alterar o perfil; outros métodos recebem HTTP 405.

As respostas administrativas usam `Cache-Control: private, no-store`, `X-Content-Type-Options: nosniff` e `X-Frame-Options: DENY`. As saídas dinâmicas são escapadas, inclusive nome, e-mail, pesquisa, categoria, nome da arte e status desconhecidos.

Não há migração nova: a funcionalidade usa as tabelas existentes de usuários, produtos, pedidos, carrinho, artes e solicitações de cancelamento. Além da consulta de perfil da sessão, cada modo de leitura utiliza duas consultas, sem consulta por usuário/pedido dentro de laços PHP. A seleção das contas é limitada antes das agregações; pedidos e carrinho são agregados separadamente para evitar multiplicação de valores. Valores monetários permanecem como `NUMERIC` no banco e texto no PHP.

A paginação numerada usa LIMIT/OFFSET com ordenação estável. Para históricos muito profundos, avaliar paginação por cursor e índices com planos reais, conforme o volume; esta entrega não cria índices nem altera a estrutura persistente.

### Verificação

A alteração de perfis passou em **79 verificações de integração e HTTP**: promoção e rebaixamento, persistência dos booleanos, seletor visível na própria conta, alteração do próprio perfil, proteção do último administrador inclusive com dois processos simultâneos, entradas inválidas, usuário inexistente, CSRF, acesso indevido, métodos HTTP, escape de nomes, mensagens, preservação da busca/paginação e atualização de sessões já abertas nas páginas administrativas. Os arquivos PHP alterados passaram no lint.

Os testes usaram somente contas e tabelas de um schema isolado, removido ao terminar; não alteraram clientes, pedidos ou artes da instalação. As ferramentas locais de verificação permanecem ignoradas pelo Git. A entrega inicial da consulta também registrou 40 verificações de integração e 55 de HTTP/navegador; esses números são históricos e anteriores à edição de perfis.

<a id="estatisticas"></a>

## Estatísticas administrativas

A página [admin/estatisticas.php](../admin/estatisticas.php) substitui o conteúdo provisório por um painel
de pedidos. O acesso é pelo menu **Administração → Estatísticas** e exige sessão administrativa, usando
a mesma guarda das demais páginas de administração. Visitantes são encaminhados ao login e clientes
recebem HTTP 403. A resposta autenticada usa `Cache-Control: private, no-store`.

### Filtro e leitura do período

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

### Definição dos indicadores

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

### Gráficos e tabelas

- **Pedidos por status:** contagem e valor por situação atual. Os cinco status conhecidos aparecem
  mesmo sem ocorrências; status antigos diferentes desses também são exibidos, escapados como texto.
- **Evolução mensal:** pedidos e cancelados pela data de criação, com o valor não cancelado. Meses sem
  movimento são preenchidos com zero. No primeiro e último mês, só entram as datas efetivamente selecionadas.
- **Pedidos por categoria:** quantidade de pedidos, unidades não canceladas e valor não cancelado,
  ordenados pelo maior valor. Categorias só aparecem quando têm pedidos no período.

Os gráficos utilizam barras HTML nativas (`meter`) com rótulos e números textuais; tabelas têm cabeçalhos
e legendas. A página tem layout responsivo, navegação por teclado, link para pular ao conteúdo, rolagem
horizontal nas tabelas em telas pequenas e estilos para impressão. Não depende de CDN ou biblioteca de gráficos.

### Dados incompletos e exceções

Pedidos com `valor_total = NULL` entram nas contagens, mas são excluídos das somas e médias monetárias.
O painel mostra quantos estão sem valor, evitando confundir ausência de preço com preço igual a zero.
Se não houver pedidos, apresenta os indicadores zerados e uma mensagem para ajustar as datas.

Ao aprovar um cancelamento, o pedido passa a sair dos valores não cancelados. Ao recusar a solicitação,
o status de produção é preservado. Pedidos excluídos definitivamente deixam de aparecer; sem uma tabela
de eventos/histórico analítico não há como continuar contando registros removidos.

Erros de consulta retornam HTTP 500 com mensagem genérica. Detalhes da exceção não são exibidos ao
administrador; o log registra o código. O [product backlog](product-backlog.md) acompanha as melhorias
de sessão e autorização. O perfil de acesso atual é revalidado no banco a cada requisição autenticada.

### Implementação e banco

- [include/estatisticas.php](../include/estatisticas.php): validação de datas e consulta agregada, com
  parâmetros preparados e verificação de sessão administrativa também na função de consulta.
- [admin/estatisticas.php](../admin/estatisticas.php): controle de acesso, filtros, indicadores e renderização.
- [admin/estatisticas.css](../admin/estatisticas.css): componentes específicos do painel; fundamentos, cabeçalho e rodapé usam [css/estilo.css](../css/estilo.css) e o template compartilhado.

Uma única instrução SQL calcula resumo, status, categorias e meses no mesmo snapshot, sem executar
consultas dentro dos loops de apresentação ou carregar os pedidos completos no PHP. Os valores monetários
permanecem como `NUMERIC` no PostgreSQL e texto no retorno JSON, preservando os centavos sem conversão em float.

Não há migração nova nem alterações dos dados existentes. São necessárias as tabelas base e a tabela de
solicitações de cancelamento, incluída no [dump SQL](../database/dump_banco.sql). Índices adicionais
para crescimento do volume estão previstos no backlog; o filtro de datas não aplica funções a `pedido.data`,
permitindo aproveitar um índice adequado quando ele for criado.

### Verificação desta entrega

A lógica foi verificada com 26 condições executadas diretamente contra tabelas temporárias: período padrão,
datas inválidas/bissextas, ausência de pedidos, limites das datas, estados e clientes, somas decimais,
ticket, valores ausentes, histórico de solicitações sem duplicação, meses zerados, categorias e autorização.
Nenhuma tabela persistente foi alterada nessas verificações. A suíte anteriormente removida não foi recriada.

Também passaram 18 verificações HTTP com servidor local temporário, cobrindo visitante/cliente/administrador,
papel administrativo inválido, formulário, gráficos, navegação atual, cache privado, estado vazio, filtros
inválidos, escape de texto vindo do banco, instalação em subpasta e carregamento do CSS. O navegador
automatizado não conseguiu gerar capturas por restrição de GPU/permissões nesta estação; não é declarada
aprovação visual automatizada. Os arquivos e processos temporários da verificação foram encerrados/removidos.
