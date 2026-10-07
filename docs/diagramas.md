# ArtUP — modelo PostgreSQL

Este diagrama corresponde às tabelas de [SQL_tabelas.pgsql](SQL_tabelas.pgsql). `PK` significa chave primária, `FK` chave estrangeira e `UK` valor único. Os IDs são gerados automaticamente.

```mermaid
erDiagram
    usuarios ||--o{ carrinho : possui
    usuarios ||--o{ pedido : faz
    produto ||--o{ pedido : aparece_em
    pedido ||--o{ carrinho : referenciado_por
    categorias ||--o{ produto : agrupa
    categorias ||--o| precodimensoes : possui_preco
    categorias ||--o{ tipos_de_produto : agrupa
    tipos_de_produto o|--o{ produto : classifica

    usuarios {
        INT id PK "NOT NULL"
        VARCHAR nome "255; NOT NULL"
        VARCHAR email "255; NOT NULL"
        VARCHAR senha "255; NOT NULL; hash"
        BOOLEAN nivel "NOT NULL"
    }
    categorias {
        INT id PK "NOT NULL"
        VARCHAR nome "255; NOT NULL"
    }
    produto {
        INT id PK "NOT NULL"
        INT id_categoria FK "NOT NULL"
        INT quantidade "NOT NULL"
        REAL largura "NULL"
        REAL comprimento "NULL"
        VARCHAR tamanho "50; NULL"
        VARCHAR grossura "3; NULL"
        INT id_tipo FK "NULL"
    }
    pedido {
        INT id PK "NOT NULL"
        INT id_usuario FK "NOT NULL"
        INT id_produto FK "NOT NULL"
        TIMESTAMP data "Sem fuso horario; NOT NULL"
        VARCHAR status "50; NOT NULL"
    }
    carrinho {
        INT id PK "NOT NULL"
        INT id_usuario FK "NOT NULL"
        INT id_pedido FK "NOT NULL"
    }
    precodimensoes {
        INT id PK "NOT NULL"
        INT id_categoria FK, UK "NOT NULL"
        NUMERIC valormetro "12,2; NULL"
    }
    tipos_de_produto {
        INT id PK "NOT NULL"
        INT id_categoria FK "NOT NULL"
        VARCHAR nome "255; NOT NULL"
        NUMERIC valor_base "12,2; NOT NULL"
    }
```

Cada pedido referencia **um produto**. Cada carrinho referencia **um pedido** e acessa seu produto por essa relação. Para permitir vários produtos no mesmo pedido ou carrinho, será necessário um modelo com tabelas de itens.

O preço por metro pertence à categoria, que pode ter zero ou um registro em `precodimensoes`. O produto acessa esse preço pela categoria; não existe uma relação direta 1:1 entre produto e preço. Uma categoria pode ter vários tipos de produto, e o tipo de um produto é opcional.

Os nomes aparecem em minúsculas porque o PostgreSQL converte identificadores sem aspas para minúsculas. As colunas antigas `tipo` e `acabamento` podem permanecer em bancos anteriores durante a transição, mas não fazem parte deste modelo.
