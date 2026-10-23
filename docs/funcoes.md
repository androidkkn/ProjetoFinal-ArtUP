# Funções do ArtUP

Referência resumida das **42 funções PHP nomeadas** do projeto, conferida em 23/10/2026. As tabelas estão organizadas pelo arquivo que declara cada função. Para o fluxo das telas, consulte [funcionalidades](funcionalidades.md); para instalação, consulte o [README principal](../README.md).

## Funções centrais

Arquivo: [include/functions_core.php](../include/functions_core.php).

| Função | O que faz |
| --- | --- |
| `formatar_preco()` | Formata um valor como `R$ 1.234,50`; para `null`, mostra “Não calculado”. Não arredonda casas excedentes. |
| `validar_inteiro_positivo()` | Aceita inteiros de 1 a 2.147.483.647 e retorna `false` para entradas inválidas. |
| `usuario_e_admin()` | Verifica se a sessão possui usuário e perfil administrativo estritamente igual a `true`. |
| `normalizar_dimensao()` | Converte vírgula decimal em ponto e valida dimensões positivas e finitas, retornando o texto normalizado ou `false`. |
| `dimensao_valida()` | Retorna se uma medida é aceita por `normalizar_dimensao()`. |
| `calcular_preco_produto()` | Calcula preço unitário e total no PostgreSQL, usando medidas em cm ou fatores de papel A1–A8. Arredonda a unidade antes de multiplicar pela quantidade. |
| `cproduto()` | Revalida o preço e cria o produto e seu item de carrinho, vinculando a arte quando informada, na mesma transação. Ainda não cria um pedido. |
| `meus_pedidos()` | Lista os pedidos de um usuário, com produto, preços, arte e última solicitação de cancelamento; permite filtrar por produto. |
| `carrinho()` | Consulta os itens do carrinho e seus valores; informa o total somente quando todos os itens possuem preço. |
| `calcular_precos_carrinho()` | Preenche os valores ausentes dos itens do carrinho do usuário da sessão, preservando os preços já calculados. |
| `excluir_produto_carrinho()` | Remove um item do carrinho do usuário informado e descarta o produto e sua arte somente se não houver outros vínculos. |
| `confirmar_pedidos()` | Transfere atomicamente os itens com preço do carrinho para pedidos pendentes, preservando os valores aceitos. |
| `listar_pedidos_admin()` | Lista pedidos para administradores e permite pesquisar pelo nome do cliente ou ID do pedido. |
| `alterar_status_pedido()` | Atualiza um pedido para um status permitido; ao cancelar ou entregar, também resolve uma solicitação de cancelamento pendente. |
| `validar_preco()` | Normaliza vírgula decimal e aceita valores não negativos com até dez dígitos inteiros e duas casas decimais. |
| `listar_produtos_admin()` | Reúne tipos, categorias, tarifas e itens de pedidos para a edição administrativa. |
| `editar_tipo_produto()` | Altera o nome e o valor base de um tipo de produto, após validar os dados e o acesso administrativo. |
| `editar_categoria_preco()` | Altera a categoria e insere ou atualiza sua tarifa por m² em uma transação. |
| `editar_item_pedido()` | Edita quantidade, medidas, formato ou espessura e recalcula os preços dos pedidos vinculados ao produto. |
| `normalizar_email()` | Remove espaços nas extremidades, converte para minúsculas e valida o formato e o limite de 255 bytes. |
| `validar_senha()` | Verifica a presença de pelo menos uma letra e um número e rejeita byte nulo. |
| `cadastrar_user()` | Valida os dados e grava uma conta com senha em hash Argon2id e o perfil recebido; informa erros pelo parâmetro `$erro`. O cadastro público passa `false` para administrador. |
| `consulta_user()` | Busca ID, e-mail, senha e perfil pelo e-mail normalizado para o processo de autenticação. |

Medidas entram em centímetros, mas `produto.largura` e `produto.comprimento` são persistidos em metros. As tarifas continuam em R$/m². Consulte as [regras de preços](funcionalidades.md#precos).

## Artes e uploads

Arquivo: [include/artes.php](../include/artes.php).

| Função | O que faz |
| --- | --- |
| `limite_php_bytes()` | Converte valores do PHP como `2M`, `512K` ou `1G` em bytes. |
| `limite_arte_bytes()` | Obtém o menor limite entre 10 MiB e as configurações de upload e POST, reservando espaço para os demais campos. |
| `limite_arte_formatado()` | Converte o limite efetivo para um texto em MB exibido no formulário. |
| `validar_upload_arte()` | Confere o upload HTTP, nome, tamanho, extensão, MIME e conteúdo; valida imagens com GD e faz uma checagem básica de PDF. Retorna os dados do arquivo, `null` se ausente ou `false` em erro. |
| `receber_arte_produto()` | Salva uma arte como rascunho por categoria, substitui o rascunho anterior e limpa rascunhos com mais de 24 horas. Sem novo arquivo, preserva o existente. |
| `consultar_rascunho_arte()` | Busca o rascunho válido da categoria pertencente ao usuário e remove da sessão referências expiradas ou inexistentes. |
| `remover_rascunho_arte()` | Exclui o rascunho do usuário somente enquanto ele não estiver vinculado a um produto. |
| `vincular_arte_produto()` | Associa um rascunho ainda válido ao produto, conferindo o usuário da sessão e o proprietário da arte. |
| `consultar_arte_produto()` | Obtém os metadados e o conteúdo da arte para download, permitindo acesso ao administrador ou ao proprietário com vínculo em carrinho ou pedido. |

## Cancelamento e exclusão de pedidos

Arquivo: [include/pedidos.php](../include/pedidos.php).

| Função | O que faz |
| --- | --- |
| `solicitar_cancelamento_pedido()` | Registra a solicitação do cliente para um pedido próprio pendente ou em produção, com motivo opcional e sem duplicar solicitações em análise. |
| `responder_cancelamento_pedido()` | Permite ao administrador aprovar ou recusar a solicitação; registra a resposta e cancela o pedido quando a aprovação é permitida. |
| `excluir_pedido_admin()` | Exclui um pedido confirmado e suas solicitações; preserva produto e arte enquanto houver outros vínculos. |

## Clientes e perfis de acesso

Arquivo: [include/clientes.php](../include/clientes.php).

| Função | O que faz |
| --- | --- |
| `alterar_perfil_cliente_admin()` | Promove ou rebaixa uma conta, revalida o administrador no banco e impede remover o último administrador. Atualiza a sessão quando a alteração é da própria conta. |
| `listar_clientes_admin()` | Lista usuários com indicadores de pedidos e carrinho, busca por nome/e-mail/ID e paginação de 25 contas. Não consulta senhas. |
| `consultar_cliente_admin()` | Retorna o resumo e o histórico paginado de pedidos de um usuário; retorna `null` para conta inexistente e `false` em falha ou acesso negado. |

`alterar_perfil_cliente_admin()` retorna `salvo`, `proibido`, `invalido`, `inexistente`, `ultimo_admin` ou `erro`, conforme o resultado da operação.

## Estatísticas

Arquivo: [include/estatisticas.php](../include/estatisticas.php).

| Função | O que faz |
| --- | --- |
| `data_estatistica_valida()` | Confere se uma entrada é uma data real no formato `AAAA-MM-DD`, com ano a partir de 1. |
| `periodo_estatisticas()` | Define o período padrão do mês atual ou valida as datas recebidas, a ordem e o limite de cinco anos; retorna início, fim e mensagem de erro. |
| `consultar_estatisticas_admin()` | Calcula indicadores, totais por status, categorias e meses em uma consulta agregada, exigindo administrador e período válido. |

## Navegação

Arquivo: [include/navegacao.php](../include/navegacao.php).

| Função | O que faz |
| --- | --- |
| `url_app()` | Monta URLs relativas à raiz da aplicação, funcionando também quando o projeto está em uma subpasta do servidor. |

## Arquivos de entrada e apoio

Estes arquivos executam ou apresentam os fluxos; não declaram funções nomeadas adicionais.

| Arquivo | Responsabilidade |
| --- | --- |
| [include/functions.php](../include/functions.php) | Carrega as funções centrais e reutiliza uma conexão PDO fornecida ou abre uma nova. |
| [database/connect.php](../database/connect.php) | Lê a configuração local e cria a conexão PDO com PostgreSQL. |
| [login/cadastrar.php](../login/cadastrar.php) | Recebe os dados de cadastro, confere a confirmação de senha e cria uma conta de cliente. |
| [login/login.php](../login/login.php) | Verifica a senha e inicia a sessão autenticada, aceitando hashes e registros legados sem hash. |
| [login/verifica_user.php](../login/verifica_user.php) | Exige login, revalida o perfil no banco e invalida o acesso quando a conta não existe mais. |
| [login/verifica_admin.php](../login/verifica_admin.php) | Aplica a guarda de usuário e retorna HTTP 403 quando o perfil não é administrativo. |
| [login/logout.php](../login/logout.php) | Valida POST e token, encerra a sessão e redireciona ao login. |
| [include/form_produto.php](../include/form_produto.php) | Compartilha entre os quatro serviços a configuração, prévia de preços, controle da arte e inclusão no carrinho. |
| [include/form_perfil_cliente.php](../include/form_perfil_cliente.php) | Apresenta o formulário de alteração de perfil na administração de clientes. |
| [app/arte.php](../app/arte.php) | Entrega a arte autorizada como arquivo para download. |
| [include/header.php](../include/header.php), [include/HeaderAdmin.php](../include/HeaderAdmin.php) e [include/footer.php](../include/footer.php) | Montam os menus por perfil e o rodapé compartilhado. |

As páginas de `app/` e `admin/` chamam essas funções para executar as ações e renderizar os resultados. Funções anônimas locais e expressões `fn` auxiliam tarefas como escape de HTML e formatação; não são funções públicas adicionais.
