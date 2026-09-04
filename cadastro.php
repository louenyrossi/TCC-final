<?php
session_start();

require_once __DIR__ . '/config/config.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $tipo = $_POST['tipo'] ?? 'aluno';

    // Validação dos campos
    if ($nome === '' || $email === '' || $senha === '' || $confirmar_senha === '') {
        $erro = 'Preencha todos os campos.';
    }

    // Validação do nome
    elseif (strlen($nome) < 3) {
        $erro = 'Digite um nome válido.';
    }

    // Validação do e-mail
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Digite um e-mail válido.';
    }

    // Validação do tipo
    elseif (!in_array($tipo, ['aluno', 'professor'], true)) {
        $erro = 'Tipo de usuário inválido.';
    }

    // Validação da senha
    elseif (strlen($senha) < 6) {
        $erro = 'A senha deve possuir pelo menos 6 caracteres.';
    }

    // Confirmação da senha
    elseif ($senha !== $confirmar_senha) {
        $erro = 'As senhas não coincidem.';
    }

    else {

        // Verifica se o e-mail já existe
        $stmt = $pdo->prepare("
            SELECT id
            FROM usuarios
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        if ($stmt->fetch()) {

            $erro = 'Este e-mail já está cadastrado.';

        } else {

            // Protege a senha
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            // Cadastra o usuário
            $stmt = $pdo->prepare("
                INSERT INTO usuarios
                (nome, email, senha, tipo, nivel, xp)
                VALUES (?, ?, ?, ?, 1, 0)
            ");

            $stmt->execute([
                $nome,
                $email,
                $senha_hash,
                $tipo
            ]);

            $sucesso = 'Cadastro realizado com sucesso! Você já pode entrar na plataforma.';

            // Limpa os campos depois do cadastro
            $nome = '';
            $email = '';
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
        content="Crie sua conta no MathPlay"
    >

    <title>Cadastro | MathPlay</title>

    <link
        rel="stylesheet"
        href="assets/css/cadastro.css"
    >
</head>

<body>

    <main class="cadastro-container">

        <section class="cadastro-card">

            <div class="logo-area">
                <div class="logo-icon">M</div>

                <div>
                    <h1>Math<span>Play</span></h1>
                    <p>Aprender matemática pode ser divertido!</p>
                </div>
            </div>


            <div class="form-header">
                <h2>Criar sua conta</h2>

                <p>
                    Preencha seus dados para começar sua jornada.
                </p>
            </div>


            <?php if ($erro !== ''): ?>

                <div class="mensagem mensagem-erro">
                    <span>!</span>
                    <p><?= htmlspecialchars($erro) ?></p>
                </div>

            <?php endif; ?>


            <?php if ($sucesso !== ''): ?>

                <div class="mensagem mensagem-sucesso">
                    <span>✓</span>

                    <p>
                        <?= htmlspecialchars($sucesso) ?>
                    </p>
                </div>

            <?php endif; ?>


            <form
                id="cadastroForm"
                method="POST"
                action="cadastro.php"
                novalidate
            >

                <div class="campo">

                    <label for="nome">
                        Nome completo
                    </label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        placeholder="Digite seu nome"
                        value="<?= htmlspecialchars($nome ?? '') ?>"
                        autocomplete="name"
                        required
                    >

                    <small class="erro-campo" id="erroNome"></small>

                </div>


                <div class="campo">

                    <label for="email">
                        E-mail
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="seuemail@exemplo.com"
                        value="<?= htmlspecialchars($email ?? '') ?>"
                        autocomplete="email"
                        required
                    >

                    <small class="erro-campo" id="erroEmail"></small>

                </div>


                <div class="campo">

                    <label>
                        Você é:
                    </label>

                    <div class="tipo-container">

                        <label class="tipo-option">

                            <input
                                type="radio"
                                name="tipo"
                                value="aluno"
                                <?= (($tipo ?? 'aluno') === 'aluno') ? 'checked' : '' ?>
                            >

                            <span class="tipo-card">

                                <span class="tipo-icon">🎓</span>

                                <span class="tipo-texto">
                                    <strong>Aluno</strong>
                                    <small>Quero aprender</small>
                                </span>

                            </span>

                        </label>


                        <label class="tipo-option">

                            <input
                                type="radio"
                                name="tipo"
                                value="professor"
                                <?= (($tipo ?? '') === 'professor') ? 'checked' : '' ?>
                            >

                            <span class="tipo-card">

                                <span class="tipo-icon">👨‍🏫</span>

                                <span class="tipo-texto">
                                    <strong>Professor</strong>
                                    <small>Quero ensinar</small>
                                </span>

                            </span>

                        </label>

                    </div>

                    <small class="erro-campo" id="erroTipo"></small>

                </div>


                <div class="campo">

                    <label for="senha">
                        Senha
                    </label>

                    <div class="senha-container">

                        <input
                            type="password"
                            id="senha"
                            name="senha"
                            placeholder="Mínimo de 6 caracteres"
                            autocomplete="new-password"
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

                    <div class="senha-forca">

                        <div class="forca-barra">
                            <span id="forcaProgresso"></span>
                        </div>

                        <small id="forcaTexto">
                            Digite uma senha
                        </small>

                    </div>

                    <small class="erro-campo" id="erroSenha"></small>

                </div>


                <div class="campo">

                    <label for="confirmar_senha">
                        Confirmar senha
                    </label>

                    <div class="senha-container">

                        <input
                            type="password"
                            id="confirmar_senha"
                            name="confirmar_senha"
                            placeholder="Digite a senha novamente"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="mostrar-senha"
                            id="mostrarConfirmarSenha"
                            aria-label="Mostrar confirmação da senha"
                        >
                            👁
                        </button>

                    </div>

                    <small
                        class="erro-campo"
                        id="erroConfirmarSenha"
                    ></small>

                </div>


                <button
                    type="submit"
                    class="btn-cadastrar"
                    id="btnCadastrar"
                >
                    <span>Criar minha conta</span>
                    <span class="btn-seta">→</span>
                </button>

            </form>


            <div class="login-link">

                <p>
                    Já possui uma conta?
                    <a href="login.php">Entrar</a>
                </p>

            </div>

        </section>


        <aside class="cadastro-lateral">

            <div class="lateral-content">

                <span class="lateral-badge">
                    🚀 Aprenda jogando
                </span>

                <h2>
                    Sua jornada matemática
                    começa aqui!
                </h2>

                <p>
                    Resolva desafios, conquiste medalhas,
                    acumule XP e evolua no MathPlay.
                </p>

                <div class="beneficios">

                    <div class="beneficio">
                        <span>🎮</span>
                        <div>
                            <strong>Jogos educativos</strong>
                            <small>Aprenda matemática de forma divertida.</small>
                        </div>
                    </div>

                    <div class="beneficio">
                        <span>🏆</span>
                        <div>
                            <strong>Conquistas</strong>
                            <small>Desbloqueie medalhas durante sua evolução.</small>
                        </div>
                    </div>

                    <div class="beneficio">
                        <span>📈</span>
                        <div>
                            <strong>Acompanhe seu progresso</strong>
                            <small>Veja seu nível, XP e desempenho.</small>
                        </div>
                    </div>

                </div>

            </div>

        </aside>

    </main>


    <script src="assets/js/cadastro.js"></script>

</body>

</html>