<?php
require_once __DIR__ . '/carrinho_fixture.php';

function conferir($condicao, $mensagem)
{
    if (!$condicao) {
        throw new RuntimeException($mensagem);
    }
}

preparar_carrinho_teste($conexao);
$_SESSION = ['id' => 1, 'admin' => true];
$itens = carrinho($conexao, 1);
conferir(count($itens) === 2 && $itens[0]['total_carrinho'] === '3.52', 'Migração deve preservar itens e soma por cliente.');
conferir(count(meus_pedidos($conexao, 1)) === 1, 'Rascunhos não devem aparecer em Meus pedidos.');
conferir(meus_pedidos($conexao, 1)[0]['valor_total'] === '50.00', 'Pedido confirmado deve permanecer intacto.');
conferir(count(listar_pedidos_admin($conexao)) === 1, 'Administrador só deve receber pedidos confirmados.');
conferir(excluir_produto_carrinho($conexao, 2, 1) === false, 'Outro cliente não pode excluir o item.');
conferir(excluir_produto_carrinho($conexao, 1, 1) === true, 'O cliente deve conseguir excluir seu item.');
conferir((int) $conexao->query('SELECT COUNT(*) FROM produto WHERE id = 1')->fetchColumn() === 0, 'Excluir rascunho deve remover também seu produto.');
conferir(carrinho($conexao, 1)[0]['total_carrinho'] === '1.02', 'Excluir deve atualizar a soma.');
conferir(confirmar_pedidos($conexao, 2) === false, 'A confirmação deve usar o cliente logado.');
conferir(confirmar_pedidos($conexao, 1) === true, 'Confirmação deve criar o pedido.');
conferir(carrinho($conexao, 1) === [], 'Confirmação deve esvaziar somente o carrinho do cliente.');
conferir(count(carrinho($conexao, 2)) === 1, 'Confirmação não pode alterar o carrinho de outro cliente.');
$pedido = meus_pedidos($conexao, 1)[0];
conferir($pedido['status'] === 'pendente' && $pedido['preco_unitario'] === '0.34' && $pedido['valor_total'] === '1.02', 'Confirmação deve preservar valores e iniciar como pendente.');
conferir(confirmar_pedidos($conexao, 1) === false && count(meus_pedidos($conexao, 1)) === 2, 'Repetir confirmação não deve duplicar pedidos.');

$numero_pedidos = (int) $conexao->query('SELECT COUNT(*) FROM pedido')->fetchColumn();
conferir(cproduto($conexao, 1, 2, '10.5', '20.5', null, null, 2), 'Adesivo deve entrar no carrinho.');
conferir(cproduto($conexao, 2, 3, null, null, 'A3', null, 8), 'Papelaria deve entrar no carrinho.');
conferir(cproduto($conexao, 3, 1, 10, 20, null, '2', null), 'Placa deve entrar no carrinho.');
conferir(cproduto($conexao, 4, 1, 10, 20, null, null, 4), 'Banner deve entrar no carrinho.');
conferir((int) $conexao->query('SELECT COUNT(*) FROM pedido')->fetchColumn() === $numero_pedidos, 'Adicionar produto não pode criar um pedido.');
conferir(count(carrinho($conexao, 1)) === 4 && count(listar_produtos_admin($conexao)['itens']) === 2, 'Itens em verificação devem ficar separados dos pedidos do administrador.');
conferir(cproduto($conexao, 1, 0, 10, 20, null, null, 2) === false, 'Quantidade zero deve ser rejeitada.');
conferir(cproduto($conexao, 1, 1, 10, 20, null, null, 8) === false, 'Tipo de outra categoria deve ser rejeitado.');
conferir(carrinho($conexao, 1)[0]['total_carrinho'] === null, 'Preço ainda ausente não deve ser tratado como zero.');

// Simula falha no destino: o DELETE do carrinho deve ser desfeito automaticamente.
$conexao->exec("CREATE FUNCTION pg_temp.falhar_confirmacao() RETURNS trigger LANGUAGE plpgsql AS \$\$
    BEGIN RAISE EXCEPTION 'Falha simulada de confirmação'; END; \$\$");
$conexao->exec('CREATE TRIGGER falha_confirmacao BEFORE INSERT ON pedido FOR EACH ROW EXECUTE FUNCTION pg_temp.falhar_confirmacao()');
conferir(confirmar_pedidos($conexao, 1) === false, 'Falha de gravação deve ser comunicada.');
conferir(count(carrinho($conexao, 1)) === 4 && (int) $conexao->query('SELECT COUNT(*) FROM pedido')->fetchColumn() === $numero_pedidos, 'Falha de confirmação deve preservar todos os rascunhos.');
$conexao->exec('DROP TRIGGER falha_confirmacao ON pedido');

// Uma falha ao descartar o produto também deve preservar o item do carrinho.
$id_carrinho = carrinho($conexao, 1)[0]['id_carrinho'];
$conexao->exec('CREATE TRIGGER falha_exclusao BEFORE DELETE ON produto FOR EACH ROW EXECUTE FUNCTION pg_temp.falhar_confirmacao()');
conferir(excluir_produto_carrinho($conexao, 1, $id_carrinho) === false, 'Falha ao excluir deve ser comunicada.');
conferir(count(carrinho($conexao, 1)) === 4, 'Falha ao excluir não pode perder o item.');
$conexao->exec('DROP TRIGGER falha_exclusao ON produto');

$_SESSION['id'] = 999;
$numero_produtos = (int) $conexao->query('SELECT COUNT(*) FROM produto')->fetchColumn();
conferir(cproduto($conexao, 1, 1, 10, 20, null, null, 2) === false, 'Usuário inexistente deve ser rejeitado pelo banco.');
conferir((int) $conexao->query('SELECT COUNT(*) FROM produto')->fetchColumn() === $numero_produtos, 'Falha de inclusão não deve deixar produtos sem vínculo.');
$_SESSION['id'] = 1;
conferir(confirmar_pedidos($conexao, 1) === true && count(meus_pedidos($conexao, 1)) === 6, 'As quatro categorias devem ser confirmadas juntas.');

conferir(formatar_preco(null) === 'Não calculado', 'Um preço ausente deve ser identificado.');
conferir(formatar_preco('123456789012345678.90') === 'R$ 123.456.789.012.345.678,90', 'Formatação deve preservar centavos.');
echo 'OK: migração, inclusão das quatro categorias, exclusão, confirmação, isolamento por cliente, preços e recuperação de falhas.' . PHP_EOL;
