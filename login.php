
<?php

session_start();

include "config.php";

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST["email"];
    $senha = $_POST["senha"];

    $sql = "SELECT * FROM usuarios WHERE email = ?";

    $stmt = $conexao->prepare($sql);

    $stmt->bind_param("s", $email);

    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows == 1) {

        $usuario = $resultado->fetch_assoc();

        if (password_verify($senha, $usuario["senha"])) {

            $_SESSION["usuario_id"] = $usuario["id"];
            $_SESSION["nome"] = $usuario["nome"];
            $_SESSION["tipo"] = $usuario["tipo"];
            $_SESSION["nivel"] = $usuario["nivel"];
            $_SESSION["xp"] = $usuario["xp"];

            header("Location: inicio.php");
            exit;

        } else {

            $mensagem = "E-mail ou senha incorretos.";

        }

    } else {

        $mensagem = "E-mail ou senha incorretos.";

    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | MathPlay</title>

    <link rel="stylesheet" href="css/login.css">

</head>

<body>

    <main class="pagina">

        <section class="login">

            <div class="logo">

                <span>✦</span>

                <h1>MathPlay</h1>

            </div>


            <div class="titulo">

                <h2>Bem-vindo de volta!</h2>

                <p>
                    Entre para continuar sua jornada.
                </p>

            </div>


            <form method="POST" id="formLogin">

                <div class="campo">

                    <label for="email">
                        E-mail
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Digite seu e-mail"
                        required
                    >

                </div>


                <div class="campo">

                    <label for="senha">
                        Senha
                    </label>

                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Digite sua senha"
                        required
                    >

                </div>


                <button type="submit">
                    Entrar
                </button>

            </form>


            <?php if ($mensagem != ""): ?>

                <p class="mensagem">
                    <?php echo $mensagem; ?>
                </p>

            <?php endif; ?>


            <p class="cadastro">

                Ainda não possui uma conta?

                <a href="cadastro.php">
                    Criar conta
                </a>

            </p>

        </section>

    </main>


    <script src="js/login.js"></script>

</body>

</html>
```
