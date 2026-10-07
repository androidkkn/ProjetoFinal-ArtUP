<?php require_once __DIR__ . '/../include/functions.php'; ?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagina de login</title>
</head>

<body>
    <main>
        <section>
            <h1>Faça o cadastro para continuar</h1>
            <form action="" method="post">
                <div>
                    <input type="text" name="nome" id="nome" placeholder="Digite o seu Nome">
                </div>
                <div>
                    <input type="email" name="email" id="email" placeholder="Digite seu email">
                </div>
                <div>
                    <input type="password" name="senha" id="senha" placeholder="digite sua senha">
                </div>
                <div>
                    <label for="nivel">Tipo de usuario</label>
                    <br>
                    <select name="nivel" id="nivel" >
                        <option value="CL">Cliente</option>
                        <option value="ADM">Admin</option>
                    </select>
                </div>
                <div>
                    <input type="submit" value="Cadastrar">
                </div>
            </form>
            <div>
                <a href="login.php">Caso já tenha cadastro clique aqui</a>
            </div>
        </section>
        <?php

        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            if ($_POST['nivel'] == "CL") {
                $tipo = 0;
            } elseif ($_POST['nivel'] == "ADM") {
                $tipo = 1;
            } else {
                echo "Opção Inválida, tente novamente.";
            }
            cadastrar_user($conexao, $_POST['nome'], $_POST['email'], $_POST['senha'], $tipo);
            // header("location: /../index.php");
            // exit();
        }

        ?>
    </main>
</body>

</html>