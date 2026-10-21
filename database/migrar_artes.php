<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/connect.php';
try {
    $conexao->exec(file_get_contents(__DIR__ . '/migrations/001_artes.sql'));
    echo "Estrutura de artes criada/verificada com sucesso.\n";
} catch (PDOException $e) {
    if ($conexao->inTransaction()) {
        $conexao->rollBack();
    }
    fwrite(STDERR, 'Não foi possível preparar a estrutura de artes. Código: ' . $e->getCode() . PHP_EOL);
    exit(1);
}
