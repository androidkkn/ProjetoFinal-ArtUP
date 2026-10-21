<?php

require_once __DIR__ . '/functions_core.php';

// Reutiliza uma conexão PDO fornecida pelo chamador.
if (!isset($conexao) || !($conexao instanceof PDO)) {
    require __DIR__ . '/../database/connect.php';
}
