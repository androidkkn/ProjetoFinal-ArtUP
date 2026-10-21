# Page dependencies

Common header tree:
- include/header.php
  - include/navegacao.php
  - include/HeaderAdmin.php (admin only)
    - include/navegacao.php

Common business tree (not visual context):
- include/functions.php
  - include/functions_core.php
    - include/artes.php
    - include/pedidos.php
  - database/connect.php
    - database/credenciais.php (private local secret: never read or export)

## Home
- index.php
  - login/verifica_user.php -> include/navegacao.php
  - common header tree
  - include/footer.php

## Authentication
- login/login.php / login/cadastrar.php
  - common business tree
  - common header tree
  - include/navegacao.php (login)

## Catalogue
- app/catalogo.php
  - common business tree
  - login/verifica_user.php -> include/navegacao.php
  - common header tree
  - include/footer.php

## Product configuration
- app/servicos/{adesivos,banner,papelaria,placas}.php
  - include/form_produto.php
    - common business tree
    - login/verifica_user.php -> include/navegacao.php
    - common header tree
    - include/footer.php

## Cart and own orders
- app/carrinho.php / app/meus_pedidos.php
  - common business tree
  - login/verifica_user.php -> include/navegacao.php
  - common header tree
  - include/footer.php

## Admin orders and products
- admin/pedidos.php / admin/produtos.php
  - login/verifica_admin.php -> login/verifica_user.php -> include/navegacao.php
  - common business tree
  - common header tree
  - include/footer.php

## Admin statistics (selected style reference)
- admin/estatisticas.php
  - login/verifica_admin.php -> login/verifica_user.php -> include/navegacao.php
  - common business tree
  - include/estatisticas.php (aggregation; no UI)
  - common header tree
  - css/estilo.css (shared shell and visual foundations)
  - admin/estatisticas.css (statistics components)
  - include/footer.php

All HTML pages load css/estilo.css and the shared footer. Statistics additionally loads admin/estatisticas.css for its own components. Registration explicitly loads include/navegacao.php before the CSS URL is rendered. Home, catalogue and product forms use the five generic images in img/; see img/README.md for provenance. These snapshots were refreshed during review of the Antigravity visual integration.
