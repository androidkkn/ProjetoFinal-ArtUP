# Routes

PHP file routes, no router or frontend build. Base paths resolved by include/navegacao.php; support root and subdirectory deployments.

| URL | Entry | Purpose |
| --- | --- | --- |
| /index.php | index.php | Authenticated home with hero, steps and four illustrated service cards |
| /login/login.php | login/login.php | Sign in |
| /login/cadastrar.php | login/cadastrar.php | Registration |
| /app/catalogo.php | app/catalogo.php | Four print categories |
| /app/servicos/adesivos.php | app/servicos/adesivos.php | Shared product form, category 1 |
| /app/servicos/papelaria.php | app/servicos/papelaria.php | Shared product form, category 2 |
| /app/servicos/placas.php | app/servicos/placas.php | Shared product form, category 3 |
| /app/servicos/banner.php | app/servicos/banner.php | Shared product form, category 4 |
| /app/carrinho.php | app/carrinho.php | Review prices and confirm orders |
| /app/meus_pedidos.php | app/meus_pedidos.php | Search own orders and request cancellation |
| /admin/pedidos.php | admin/pedidos.php | Manage statuses, cancellations and deletions |
| /admin/produtos.php | admin/produtos.php | Edit prices and order items |
| /admin/estatisticas.php | admin/estatisticas.php | Existing visual reference: filters, metrics, tables |

All HTML pages use include/header.php, its conditional admin navigation, include/footer.php and css/estilo.css. Statistics adds admin/estatisticas.css for its own components. Download /app/arte.php and POST /login/logout.php are action endpoints, not UI pages.
