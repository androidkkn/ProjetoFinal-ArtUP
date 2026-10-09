<?php
require_once __DIR__ . '/database/connect.php';
$consultas = [
    'colunas' => "SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'carrinho' ORDER BY ordinal_position",
    'referencias' => "SELECT conrelid::regclass::text AS tabela, pg_get_constraintdef(oid) AS definicao FROM pg_constraint WHERE contype = 'f' AND (conrelid = 'public.carrinho'::regclass OR confrelid IN ('public.pedido'::regclass, 'public.produto'::regclass))",
    'resumo' => "SELECT pe.status, COUNT(*) AS itens, COUNT(*) FILTER (WHERE c.id_usuario <> pe.id_usuario) AS usuarios_divergentes FROM carrinho c JOIN pedido pe ON pe.id = c.id_pedido GROUP BY pe.status",
    'duplicados' => 'SELECT COUNT(*) FROM (SELECT pe.id_produto FROM carrinho c JOIN pedido pe ON pe.id = c.id_pedido GROUP BY pe.id_produto HAVING COUNT(*) > 1) repetidos',
];
foreach ($consultas as $nome => $sql) {
    echo $nome . ': ' . json_encode($conexao->query($sql)->fetchAll(PDO::FETCH_ASSOC)) . PHP_EOL;
}
