SELECT * FROM precodimensoes;
Select * FROM produto;
SELECT * FROM tipos_de_produto;
select * from categorias;
SELECT * FROM usuarios;



INSERT INTO categorias (nome) VALUES ('adesivos'), ('papelaria'), ('placas'), ('banners');
INSERT INTO tipos_de_produto (id_categoria, nome, valor_base) VALUES
(1, 'BOPP', 5.00),
(1, 'Vinil', 7.50),
(1, 'Vinil-UV', 10.00),

(4, 'PVC', 2.00),
(4, 'Barra-de-madeira', 3.00),
(4, 'Barra-de-aluminho', 4.00),
(4, 'Ilhós', 2.50),

(2, 'Sulfite', 0.50),
(2, 'Fotográfico', 1.50),
(2, 'Couché', 1.00),
(2, 'Offset', 0.75);

INSERT INTO precodimensoes (id_categoria, valormetro) VALUES
(1, 0.10),
(2, 0.05),
(3, 0.15),
(4, 0.20);

SELECT valormetro FROM precodimensoes WHERE id_categoria = 1;

