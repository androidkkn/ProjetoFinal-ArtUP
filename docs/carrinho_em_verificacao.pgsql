-- Executar uma vez no banco que ainda usa carrinho.id_pedido.
-- Itens em verificacao passam a referenciar o produto diretamente.
-- Seus dados e precos sao preservados. Nenhum registro antigo de pedido e excluido.
BEGIN;

LOCK TABLE carrinho, pedido, produto IN ACCESS EXCLUSIVE MODE;

DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM carrinho c LEFT JOIN pedido pe ON pe.id = c.id_pedido
        WHERE pe.id IS NULL OR pe.id_usuario <> c.id_usuario OR pe.status <> 'carrinho'
    ) THEN
        RAISE EXCEPTION 'Ha vinculos inconsistentes ou pedidos confirmados no carrinho. Corrija antes de migrar.';
    END IF;
    IF EXISTS (
        SELECT 1 FROM carrinho c JOIN pedido pe ON pe.id = c.id_pedido
        GROUP BY pe.id_produto HAVING COUNT(*) > 1
    ) THEN
        RAISE EXCEPTION 'Ha produtos repetidos no carrinho. Corrija antes de migrar.';
    END IF;
END $$;

ALTER TABLE carrinho
    ADD COLUMN id_produto INT,
    ADD COLUMN preco_unitario NUMERIC(12,2) CHECK (preco_unitario >= 0),
    ADD COLUMN valor_total NUMERIC(12,2) CHECK (valor_total >= 0);

UPDATE carrinho c
SET id_produto = pe.id_produto, preco_unitario = pe.preco_unitario, valor_total = pe.valor_total
FROM pedido pe WHERE pe.id = c.id_pedido;

ALTER TABLE carrinho
    ALTER COLUMN id_pedido DROP NOT NULL,
    ALTER COLUMN id_produto SET NOT NULL,
    ADD CONSTRAINT fk_carrinho_produto FOREIGN KEY (id_produto) REFERENCES produto (id),
    ADD CONSTRAINT uq_carrinho_produto UNIQUE (id_produto);

-- id_pedido permanece como referencia historica dos itens antigos.
-- Novos itens usam somente id_produto. A aplicacao oculta os antigos
-- registros com status 'carrinho' nas listagens de pedidos confirmados.

COMMIT;
