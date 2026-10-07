# Documentação breve — ArtUP

O projeto possui a estrutura inicial de um site para a gráfica ArtUP, com páginas para apresentar a empresa, mostrar os serviços e organizar as áreas de clientes e administração. Esta documentação considera apenas os arquivos fora da pasta `docs`.

## O que foi feito

- **Página inicial (`index.php`):** criada com as seções “Sobre Nós” e novidades. Os textos e algumas referências de imagens ainda são provisórios.
- **Cabeçalho (`include/header.php`):** apresenta o logotipo e os links para início, catálogo, meus pedidos e carrinho.
- **Rodapé (`include/footer.php`):** criado como arquivo compartilhado, ainda com conteúdo provisório.
- **Catálogo (`app/catalogo.php`):** apresenta oito categorias: produtos em lona, adesivos, placas, produtos rígidos, estampa em roupas, impressões 3D, papelaria e outros produtos. Os links das categorias ainda não têm destino definido.
- **Meus pedidos (`app/meus_pedidos.php`):** contém o título da página, um campo de pesquisa pelo número do produto e uma seção reservada para exibir os pedidos. A pesquisa e a listagem ainda não possuem processamento implementado.
- **Carrinho (`app/carrinho.php`):** possui a estrutura básica da página, com cabeçalho e rodapé. A inclusão e a exibição de itens ainda não foram implementadas.
- **Administração (`admin/`):** a página `estatisticas.php` contém uma navegação inicial para estatísticas, orçamento, pedidos, produtos e Kanban. Os arquivos `pedidos.php` e `produtos.php` estão vazios; as páginas de orçamento e Kanban referenciadas no menu ainda não existem.

## Como o projeto foi organizado

As páginas usam **HTML** para estruturar o conteúdo e **PHP** para incluir o cabeçalho e o rodapé. Isso permite compartilhar esses elementos entre as páginas e centralizar suas alterações.

| Local | Finalidade |
| --- | --- |
| `index.php` | Página inicial do site. |
| `app/` | Páginas destinadas ao cliente. |
| `admin/` | Arquivos da área administrativa. |
| `include/` | Cabeçalho e rodapé compartilhados. |
| `img/` | Imagens do projeto; atualmente contém o logotipo. |

## Estado atual

Foi montada a base das páginas e da navegação. Nos arquivos analisados, ainda não há conexão com banco de dados, autenticação, operações de produtos e pedidos, cálculos de estatísticas, CSS ou JavaScript. Também faltam as imagens referenciadas na página inicial e no catálogo, além da finalização dos textos e do rodapé.
