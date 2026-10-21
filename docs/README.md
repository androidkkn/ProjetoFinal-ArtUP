# Documentação do ArtUP

O [README principal](../README.md) concentra requisitos, instalação, configuração, execução local e orientações de validação. Esta pasta reúne a documentação complementar em um arquivo por assunto.

| Documento | Conteúdo |
| --- | --- |
| [Funcionalidades](funcionalidades.md) | Navegação, preços, artes, status/cancelamentos/exclusão de pedidos, consulta de clientes e estatísticas. |
| [Banco de dados](banco-de-dados.md) | Três diagramas PostgreSQL: catálogo/preços, clientes/pedidos e artes/cancelamentos; campos, cardinalidades, regras de exclusão e exemplos SQL. |
| [Auditoria histórica](auditoria.md) | Achados originais de segurança, qualidade e desempenho, com recomendações e limites. |
| [Product backlog](product-backlog.md) | Entregas, pendências, prioridades e critérios de aceitação. |
| [Histórico de revisões](revisoes.md) | Revisões de manutenção e integração visual, com datas, decisões e verificações históricas. |

## Atalhos das funcionalidades

- [Navegação e acesso](funcionalidades.md#navegacao)
- [Preços e centímetros](funcionalidades.md#precos)
- [Envio e download das artes](funcionalidades.md#artes)
- [Status, cancelamento e exclusão de pedidos](funcionalidades.md#pedidos)
- [Consulta administrativa de clientes](funcionalidades.md#clientes)
- [Estatísticas administrativas](funcionalidades.md#estatisticas)

Os antigos guias separados foram incorporados aos documentos acima. Os exemplos SQL agora ficam no [guia do banco](banco-de-dados.md#parametros-iniciais); não são instalador nem seed idempotente.

Os testes antigos foram removidos a pedido. Os resultados nos relatórios são históricos, não uma suíte disponível no checkout. A instalação a partir de banco vazio continua pendente de uma baseline completa. As imagens provisórias e seus prompts estão documentados em [img/README.md](../img/README.md).
