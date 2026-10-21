<?php

function url_app($caminho)
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $diretorio = str_replace('\\', '/', dirname($script));
    if (basename($diretorio) === 'servicos' && basename(dirname($diretorio)) === 'app') {
        $diretorio = dirname($diretorio, 2);
    } elseif (in_array(basename($diretorio), ['app', 'admin', 'login', 'include'], true)) {
        $diretorio = dirname($diretorio);
    }
    $diretorio = str_replace('\\', '/', $diretorio);
    $base = $diretorio === '.' ? '' : rtrim($diretorio, '/');
    return $base . '/' . ltrim($caminho, '/');
}
