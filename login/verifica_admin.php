<?php
require_once __DIR__ . '/verifica_user.php';

if (($_SESSION['admin'] ?? false) !== true) {
    http_response_code(403);
    exit('Acesso restrito ao administrador.');
}
