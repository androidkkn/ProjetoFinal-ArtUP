<?php
require_once __DIR__ . '/../include/functions.php';

session_start();
$erroLogin = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senhaInformada = $_POST['senha'] ?? '';
    $usuario = filter_var($email, FILTER_VALIDATE_EMAIL)
        ? consulta_user($conexao, $email)
        : false;

    $senhaValida = false;
    if ($usuario && $senhaInformada !== '') {
        // As contas antigas podem ter sido cadastradas sem hash.
        $senhaSemHash = password_get_info($usuario['senha'])['algoName'] === 'unknown';
        $senhaValida = $senhaSemHash
            ? hash_equals($usuario['senha'], $senhaInformada)
            : password_verify($senhaInformada, $usuario['senha']);
    }

    if ($senhaValida) {
        session_regenerate_id(true);
        $_SESSION['id'] = $usuario['id'];
        $_SESSION['admin'] = (bool) $usuario['admin'];
        header('Location: ../index.php');
        exit();
    }

    $erroLogin = 'Usuario ou senha invalidos!';
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagina de login</title>
</head>
<body>
    <header>
        <p>Para acessar a barra de navegação faça o seu login</p>
    </header>
    <main>
        <section>
            <h1>Faça o login para continuar</h1>
            <form action="" method="post">
                <div>
                    <input type="email" name="email" id="email" placeholder="Digite seu email" required>
                </div>
                <div>
                    <input type="password" name="senha" id="senha" placeholder="digite sua senha" required>
                </div>
                <div>
                    <input type="submit" value="Logar">
                </div>
            </form>
            <div>
                <a href="cadastrar.php">Caso não tenha cadastro clique aqui</a>
            </div>

            <?php if ($erroLogin !== ''): ?>
                <p><b><?= htmlspecialchars($erroLogin, ENT_QUOTES, 'UTF-8') ?></b></p>
            <?php endif; ?>

        </section>
    </main>
</body>
</html>
