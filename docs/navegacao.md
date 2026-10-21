# Navegação

O cabeçalho compartilhado mostra Início, Catálogo, Meus pedidos, Carrinho e Sair para usuários autenticados. Administradores também veem Administração e os links Estatísticas, Pedidos, Clientes e Produtos em todas as páginas que usam esse cabeçalho. Visitantes veem Início, Entrar e Cadastrar.

Na área administrativa, a página atual é identificada com `aria-current="page"`. Estatísticas abre o
[painel de pedidos](estatisticas.md), com filtros de período e indicadores; os atalhos do painel levam
à gestão de pedidos e à análise das solicitações de cancelamento.

**Clientes** abre a [consulta de usuários](clientes.md), com busca, indicadores e histórico individual de pedidos. O acesso permanece restrito ao administrador, inclusive ao abrir diretamente o endereço da página.

`include/navegacao.php` monta os caminhos a partir da página acessada. Os links para início, catálogo, serviços, administração, login e logotipo funcionam na raiz do servidor e em uma subpasta, inclusive nas páginas de `app/servicos`.

Sair envia um formulário POST com token da sessão para `login/logout.php`. O servidor valida o token, remove os dados da sessão, apaga o cookie e redireciona para o login. Abrir a URL por GET não encerra a sessão. A proteção das páginas administrativas continua sendo feita no servidor, independentemente da visibilidade do menu.

