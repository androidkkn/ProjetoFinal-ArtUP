# Cálculo dos preços

Os quatro serviços usam `include/form_produto.php` para mostrar uma prévia antes de adicionar ao carrinho. Os valores são calculados no servidor a partir de `tipos_de_produto.valor_base` e `precodimensoes.valormetro`.

## Regras

- **Adesivos e banners:** comprimento e largura em **centímetros (cm)**. Área em m² = comprimento × largura ÷ 10.000. Preço unitário = área em m² × preço por m² da categoria + valor base do tipo/acabamento.
- **Placas:** comprimento e largura em **centímetros (cm)**. Preço unitário = (comprimento × largura ÷ 10.000) × preço por m². Não há tipo nem adicional por espessura cadastrado; 1, 2 e 3 mm usam a mesma tarifa.
- **Papelaria:** o valor base do tipo é o preço de uma folha **A4**. Multiplicadores: A1 = 8; A2 = 4; A3 = 2; A4 = 1; A5 = 0,5; A6 = 0,25; A7 = 0,125; A8 = 0,0625. O preço por m² da categoria não entra nesse cálculo.
- O preço unitário é arredondado para duas casas decimais, com meio centavo arredondado para cima. O total é esse preço unitário × quantidade. Os cálculos usam `NUMERIC` do PostgreSQL para preservar os centavos.
- Quantidades devem ser inteiros positivos; dimensões, números positivos finitos. Preço unitário e total devem caber em `NUMERIC(12,2)`, até R$ 9.999.999.999,99. Tipos de outra categoria, tarifas ausentes ou negativas e dados inválidos impedem a inclusão.

Exemplos com os parâmetros iniciais: adesivo BOPP de 200 × 300 cm tem área de 6 m², custa R$ 5,60 por unidade e R$ 16,80 para 3 unidades; sulfite A3 = R$ 1,00 por folha; sulfite A8 = R$ 0,03 por folha após o arredondamento.

## Unidades e compatibilidade com dados existentes

Os formulários, a prévia, o carrinho, os pedidos e a edição administrativa trabalham com centímetros.
A tarifa administrativa continua em R$/m², evitando a perda de precisão que ocorreria ao dividir tarifas
pequenas por 10.000 e salvá-las numa coluna monetária de duas casas.

As colunas `produto.largura` e `produto.comprimento` continuam armazenadas em **metros**, preservando os
registros existentes. O servidor divide a entrada em centímetros por 100 ao salvar e multiplica as
medidas por 100 ao retorná-las nas funções de listagem. A prévia converte as duas dimensões com `NUMERIC`
antes de calcular a área, sem arredondamento monetário intermediário. A edição administrativa e o cálculo
de carrinhos antigos usam a mesma convenção, evitando conversão duplicada.

Não há migração de dados nem recálculo automático de preços já aceitos. Formulários abertos antes da
mudança precisam ser recarregados: o campo oculto `unidade_medidas=cm` impede interpretar valores de
formulários antigos em metros como se já fossem centímetros. A espessura das placas permanece em mm
e os formatos de papel A1–A8 mantêm os fatores existentes.

## Carrinho e pedidos

O usuário calcula a prévia e confirma a inclusão. Se os dados ou o preço mudarem, deve conferir a nova prévia. A inclusão recalcula e valida os valores; nenhum preço enviado pelo navegador é aceito como fonte do cálculo.

O carrinho armazena preço unitário e total. A confirmação preserva esses valores no pedido, mesmo que o administrador tenha alterado as tarifas depois. Itens antigos sem preço podem ser calculados pelo botão no carrinho e precisam ter valores antes da confirmação. Esse botão preenche somente os itens sem preço; não altera os já calculados.

Alterar apenas a tabela de tarifas não muda pedidos anteriores. Editar explicitamente quantidade, medidas ou tamanho de um item na administração recalcula o preço com a tabela atual, salvando dados e valores na mesma transação.

Não é necessária migração no banco atual: `carrinho` e `pedido` já possuem `preco_unitario` e `valor_total`.

