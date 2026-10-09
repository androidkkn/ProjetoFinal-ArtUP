<?php
require_once __DIR__ . '/database/connect.php';
$produtos_antes = $conexao->query('SELECT COUNT(*) FROM produto')->fetchColumn();
$itens_antes = $conexao->query('SELECT COUNT(*) FROM carrinho')->fetchColumn();
$pedidos_antes = $conexao->query('SELECT * FROM pedido ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$sql = file_get_contents(__DIR__ . '/docs/carrinho_em_verificacao.pgsql');
$sql = preg_replace('/^(BEGIN;|COMMIT;)\r?\n?/m', '', $sql);
$conexao->beginTransaction();
try {
    $conexao->exec($sql);
    if ($conexao->query('SELECT COUNT(*) FROM produto')->fetchColumn() !== $produtos_antes
        || $conexao->query('SELECT COUNT(*) FROM carrinho')->fetchColumn() !== $itens_antes
        || $conexao->query('SELECT * FROM pedido ORDER BY id')->fetchAll(PDO::FETCH_ASSOC) !== $pedidos_antes) {
        throw new RuntimeException('A migração deve preservar todos os produtos e itens do carrinho.');
    }
    $conexao->commit();
    echo 'Migração concluída; itens em verificação preservados: ' . $itens_antes . PHP_EOL;
    echo 'Pedidos gravados: ' . $conexao->query('SELECT COUNT(*) FROM pedido')->fetchColumn() . PHP_EOL;
} catch (Throwable $e) {
    if ($conexao->inTransaction()) {
        $conexao->rollBack();
    }
    throw $e;
}
