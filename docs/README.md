# ArtUP

Aplicação PHP procedural com PostgreSQL para orçamento e pedidos de uma gráfica.

- Cadastro/login de clientes e acesso administrativo por sessão.
- Adesivos, banners e placas com medidas em centímetros, cálculo no servidor e prévia antes da inclusão; papelaria por formato A1–A8.
- Carrinho e confirmação atômica em pedidos, preservando o preço aceito.
- Upload de artes PNG/JPEG/WEBP/PDF, rascunhos e download restrito ao proprietário ou administrador.
- Administração de preços, itens e status; exclusão de pedidos por administrador.
- Solicitação de cancelamento pelo cliente, decisão administrativa e histórico de respostas.
- Estatísticas administrativas com filtro por período, indicadores, distribuição por status, evolução mensal e categorias.

Alguns textos, o rodapé compartilhado e as imagens da loja/catálogo ainda precisam ser concluídos.
Há achados de segurança abertos: a aprovação funcional não representa liberação para produção.

| Documento | Conteúdo |
| --- | --- |
| [auditoria.md](auditoria.md) | Registro histórico da auditoria, com achados, severidade e correções recomendadas. |
| [estatisticas.md](estatisticas.md) | Indicadores, filtros, regras de valores e funcionamento do painel administrativo. |
| [product-backlog.md](product-backlog.md) | Entregas concluídas, prioridades, histórias, critérios de aceitação e dependências. |
| [precos.md](precos.md) | Fórmulas, arredondamento, prévia e armazenamento dos preços. |
| [artes.md](artes.md) | Formatos, limites, rascunhos, vínculo e acesso às artes. |
| [pedidos.md](pedidos.md) | Cancelamento, decisões e exclusão administrativa. |
| [navegacao.md](navegacao.md) | Links por perfil, subdiretórios e logout. |

## Execução e banco

A conexão atual lê `database/credenciais.php`, arquivo local ignorado no Git, com as variáveis `$ip`,
`$nomeDB`, `$nomeUSR` e `$senha`. Não versione seus valores. A aplicação utiliza PHP com PDO/PostgreSQL,
Fileinfo, GD e mbstring.

As migrações de [artes](../database/migrations/001_artes.sql) e
[cancelamentos](../database/migrations/002_cancelamentos.sql) exigem as tabelas base já existentes.
O checkout atual ainda precisa de uma baseline de produção reproduzível.

Os arquivos da suíte de testes, suas ferramentas e os resultados foram removidos a pedido.
O relatório de auditoria foi mantido como registro histórico.
