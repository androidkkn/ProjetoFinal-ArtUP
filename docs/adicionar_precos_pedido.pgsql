-- Executar uma vez no banco existente. Nao modifica os valores base cadastrados.
-- Pedidos antigos permanecem sem preco ate serem calculados pela aplicacao.
ALTER TABLE pedido
    ADD COLUMN IF NOT EXISTS preco_unitario NUMERIC(12,2) CHECK (preco_unitario >= 0),
    ADD COLUMN IF NOT EXISTS valor_total NUMERIC(12,2) CHECK (valor_total >= 0);
