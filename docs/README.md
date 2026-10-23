# Documentação do ArtUP

O [README principal](../README.md) concentra requisitos, instalação, configuração, execução local e orientações de validação. Esta pasta reúne a documentação complementar em um arquivo por assunto.

| Documento | Conteúdo |
| --- | --- |
| [Funcionalidades](funcionalidades.md) | Navegação, preços, artes, status/cancelamentos/exclusão de pedidos, consulta de clientes e estatísticas. |
| [Banco de dados](banco-de-dados.md) | Três diagramas PostgreSQL: catálogo/preços, clientes/pedidos e artes/cancelamentos; campos, cardinalidades e regras de exclusão. |
| [Auditoria histórica](auditoria.md) | Achados originais de segurança, qualidade e desempenho, com recomendações e limites. |
| [Product backlog](product-backlog.md) | Entregas, pendências, prioridades e critérios de aceitação. |

## Atalhos das funcionalidades

- [Navegação e acesso](funcionalidades.md#navegacao)
- [Preços e centímetros](funcionalidades.md#precos)
- [Envio e download das artes](funcionalidades.md#artes)
- [Status, cancelamento e exclusão de pedidos](funcionalidades.md#pedidos)
- [Consulta administrativa de clientes](funcionalidades.md#clientes)
- [Estatísticas administrativas](funcionalidades.md#estatisticas)

Os antigos guias separados foram incorporados aos documentos acima.

Os testes antigos foram removidos a pedido; não há suíte versionada neste checkout. A instalação em banco vazio usa o [dump SQL](../database/dump_banco.sql), conforme o [tutorial principal](../README.md#instalação-e-configuração). As imagens provisórias ficam em `/img`.
