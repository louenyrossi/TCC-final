<?php
session_start();

require_once __DIR__ . '/config/config.php';

$erro = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {

        $erro = 'Preencha o e-mail e a senha.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro = 'Digite um e-mail válido.';

    } else {

        $stmt = $pdo->prepare("
            SELECT id, nome, email, senha, tipo, nivel, xp
            FROM usuarios
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {

            session_regenerate_id(true);

            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nome'] = $usuario['nome'];
            $_SESSION['email'] = $usuario['email'];
            $_SESSION['tipo'] = $usuario['tipo'];
            $_SESSION['nivel'] = $usuario['nivel'];
            $_SESSION['xp'] = $usuario['xp'];

            if ($usuario['tipo'] === 'aluno') {

                header('Location: aluno/dashboard.php');
                exit;

            } elseif ($usuario['tipo'] === 'professor') {

                header('Location: professor/dashboard.php');
                exit;

            } elseif ($usuario['tipo'] === 'admin') {

                header('Location: professor/dashboard.php');
                exit;

            }

        } else {

            $erro = 'E-mail ou senha incorretos.';

        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Entre na plataforma MathPlay"
    >

    <title>Login | MathPlay</title>

    <link
        rel="stylesheet"
        href="assets/css/login.css"
    >

</head>

<body>

    <main class="login-container">

        <!-- LADO DO FORMULÁRIO -->

        <section class="login-card">

            <div class="logo-area">

                <div class="logo-icon">
                    M
                </div>

                <div>

                    <h1>
                        Math<span>Play</span>
                    </h1>

                    <p>
                        Aprender matemática pode ser divertido!
                    </p>

                </div>

            </div>


            <div class="form-header">

                <h2>
                    Bem-vindo de volta!
                </h2>

                <p>
                    Entre na sua conta para continuar sua jornada.
                </p>

            </div>


            <?php if ($erro !== ''): ?>

                <div
                    class="mensagem mensagem-erro"
                    role="alert"
                >

                    <span>!</span>

                    <p>
                        <?= htmlspecialchars($erro) ?>
                    </p>

                </div>

            <?php endif; ?>


            <form
                id="loginForm"
                method="POST"
                action="login.php"
                novalidate
            >

                <div class="campo">

                    <label for="email">
                        E-mail
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="seuemail@exemplo.com"
                        value="<?= htmlspecialchars($email) ?>"
                        autocomplete="email"
                        required
                    >

                    <small
                        class="erro-campo"
                        id="erroEmail"
                    ></small>

                </div>


                <div class="campo">

                    <div class="senha-label">

                        <label for="senha">
                            Senha
                        </label>

                    </div>


                    <div class="senha-container">

                        <input
                            type="password"
                            id="senha"
                            name="senha"
                            placeholder="Digite sua senha"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="mostrar-senha"
                            id="mostrarSenha"
                            aria-label="Mostrar senha"
                        >
                            👁
                        </button>

                    </div>

                    <small
                        class="erro-campo"
                        id="erroSenha"
                    ></small>

                </div>


                <button
                    type="submit"
                    class="btn-entrar"
                    id="btnEntrar"
                >

                    <span>
                        Entrar
                    </span>

                    <span class="btn-seta">
                        →
                    </span>

                </button>

            </form>


            <div class="cadastro-link">

                <p>
                    Ainda não possui uma conta?
                    <a href="cadastro.php">
                        Criar conta
                    </a>
                </p>

            </div>


            <div class="login-footer">

                <span>
                    MathPlay
                </span>

                <span>
                    •
                </span>

                <span>
                    Educação + Diversão
                </span>

            </div>

        </section>


        <!-- LADO VISUAL -->

        <aside class="login-lateral">

            <div class="lateral-content">

                <span class="lateral-badge">
                    🎮 MathPlay
                </span>

                <h2>
                    Aprenda.
                    Jogue.
                    Evolua.
                </h2>

                <p>
                    Resolva desafios matemáticos,
                    conquiste medalhas e acompanhe
                    sua evolução.
                </p>


                <div class="estatisticas">

                    <div class="estatistica">

                        <span class="estatistica-icon">
                            🎯
                        </span>

                        <div>

                            <strong>
                                Desafios
                            </strong>

                            <small>
                                Pratique matemática
                            </small>

                        </div>

                    </div>


                    <div class="estatistica">

                        <span class="estatistica-icon">
                            🏆
                        </span>

                        <div>

                            <strong>
                                Conquistas
                            </strong>

                            <small>
                                Desbloqueie medalhas
                            </small>

                        </div>

                    </div>


                    <div class="estatistica">

                        <span class="estatistica-icon">
                            ⭐
                        </span>

                        <div>

                            <strong>
                                XP e níveis
                            </strong>

                            <small>
                                Evolua jogando
                            </small>

                        </div>

                    </div>

                </div>

            </div>

        </aside>

    </main>


    <script src="assets/js/login.js"></script>

</body>

</html>