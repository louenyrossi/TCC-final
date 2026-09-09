<?php

require_once 'config/config.php';

$erro = '';
$sucesso = '';

$turmas = [];

try {

    $stmt = $pdo->query("
        SELECT id, nome, ano_serie
        FROM turmas
        WHERE ativo = 1
        ORDER BY ano_serie, nome
    ");

    $turmas = $stmt->fetchAll();

} catch (PDOException $e) {

    $erro = 'Não foi possível carregar as turmas.';

}


/*
|--------------------------------------------------------------------------
| CADASTRO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $tipo = $_POST['tipo'] ?? 'aluno';

    $turmaId = !empty($_POST['turma_id'])
        ? (int) $_POST['turma_id']
        : null;


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÕES
    |--------------------------------------------------------------------------
    */

    if ($nome === '') {

        $erro = 'Digite o nome do usuário.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro = 'Digite um e-mail válido.';

    } elseif (strlen($senha) < 6) {

        $erro = 'A senha deve ter pelo menos 6 caracteres.';

    } elseif (!in_array($tipo, ['aluno', 'professor'], true)) {

        $erro = 'Tipo de usuário inválido.';

    } elseif ($tipo === 'aluno' && $turmaId === null) {

        $erro = 'Selecione a série/turma do aluno.';

    }


    /*
    |--------------------------------------------------------------------------
    | CADASTRA USUÁRIO
    |--------------------------------------------------------------------------
    */

    if ($erro === '') {

        try {

            /*
            | Verifica e-mail
            */

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


                /*
                | Verifica turma
                */

                if ($tipo === 'aluno') {

                    $stmt = $pdo->prepare("
                        SELECT id
                        FROM turmas
                        WHERE id = ?
                        AND ativo = 1
                        LIMIT 1
                    ");

                    $stmt->execute([$turmaId]);

                    if (!$stmt->fetch()) {

                        $erro = 'A série/turma selecionada não existe.';

                    }

                }


                if ($erro === '') {

                    /*
                    | Cria hash da senha
                    */

                    $senhaHash = password_hash(
                        $senha,
                        PASSWORD_DEFAULT
                    );


                    /*
                    | Insere usuário
                    */

                    $stmt = $pdo->prepare("
                        INSERT INTO usuarios
                        (
                            nome,
                            email,
                            senha,
                            tipo,
                            nivel,
                            xp,
                            turma_id
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?,
                            1,
                            0,
                            ?
                        )
                    ");

                    $stmt->execute([
                        $nome,
                        $email,
                        $senhaHash,
                        $tipo,
                        $tipo === 'aluno' ? $turmaId : null
                    ]);


                    /*
                    | ID do novo usuário
                    */

                    $usuarioId = (int) $pdo->lastInsertId();


                    /*
                    |--------------------------------------------------------------------------
                    | CRIA PROGRESSO INICIAL DOS JOGOS
                    |--------------------------------------------------------------------------
                    */

                    if ($tipo === 'aluno') {

                        $stmt = $pdo->query("
                            SELECT id
                            FROM jogos
                            WHERE ativo = 1
                        ");

                        $jogos = $stmt->fetchAll();


                        foreach ($jogos as $jogo) {

                            $stmtProgresso = $pdo->prepare("
                                INSERT IGNORE INTO progresso
                                (
                                    usuario_id,
                                    jogo_id,
                                    dificuldade,
                                    status,
                                    porcentagem
                                )
                                VALUES
                                (
                                    ?,
                                    ?,
                                    'facil',
                                    'disponivel',
                                    0
                                )
                            ");

                            $stmtProgresso->execute([
                                $usuarioId,
                                $jogo['id']
                            ]);

                        }

                    }


                    $sucesso = 'Cadastro realizado com sucesso!';

                }

            }

        } catch (PDOException $e) {

            $erro = 'Erro ao realizar o cadastro.';

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

    <title>Cadastro | MathPlay</title>


    <link
        rel="stylesheet"
        href="assets/css/cadastro.css"
    >

</head>


<body>


<div class="cadastro-container">


    <!-- =====================================================
         LADO ESQUERDO
         ===================================================== -->

    <main class="cadastro-card">


        <!-- LOGO -->

        <div class="logo-area">

            <div class="logo-icon">
                🧮
            </div>

            <div>

                <h1>
                    Math<span>Play</span>
                </h1>

                <p>
                    Aprender matemática pode ser divertido
                </p>

            </div>

        </div>


        <!-- CABEÇALHO -->

        <div class="form-header">

            <h2>
                Criar sua conta
            </h2>

            <p>
                Preencha os dados abaixo para começar
                sua jornada no MathPlay.
            </p>

        </div>


        <!-- =================================================
             MENSAGEM DE ERRO
             ================================================= -->

        <?php if ($erro !== ''): ?>

            <div class="mensagem mensagem-erro">

                <span>!</span>

                <p>
                    <?= htmlspecialchars($erro) ?>
                </p>

            </div>

        <?php endif; ?>


        <!-- =================================================
             MENSAGEM DE SUCESSO
             ================================================= -->

        <?php if ($sucesso !== ''): ?>

            <div class="mensagem mensagem-sucesso">

                <span>✓</span>

                <p>

                    <?= htmlspecialchars($sucesso) ?>

                    <br>

                    <a href="login.php">
                        Ir para o login
                    </a>

                </p>

            </div>

        <?php endif; ?>


        <!-- =================================================
             FORMULÁRIO
             ================================================= -->

        <form
            method="POST"
            action=""
            id="formCadastro"
        >


            <!-- NOME -->

            <div class="campo">

                <label for="nome">
                    Nome completo
                </label>

                <input
                    type="text"
                    id="nome"
                    name="nome"
                    value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                    placeholder="Digite seu nome"
                    required
                >

                <span
                    class="erro-campo"
                    id="erroNome"
                ></span>

            </div>


            <!-- EMAIL -->

            <div class="campo">

                <label for="email">
                    E-mail
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    placeholder="Digite seu e-mail"
                    required
                >

                <span
                    class="erro-campo"
                    id="erroEmail"
                ></span>

            </div>


            <!-- SENHA -->

            <div class="campo">

                <label for="senha">
                    Senha
                </label>

                <div class="senha-container">

                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Crie uma senha"
                        minlength="6"
                        required
                    >

                    <button
                        type="button"
                        class="mostrar-senha"
                        id="mostrarSenha"
                        aria-label="Mostrar senha"
                    >
                        <span class="icone-olho"></span>
                    </button>

                </div>


                <div class="senha-forca">

                    <div class="forca-barra">

                        <span id="forcaBarra"></span>

                    </div>

                    <span id="forcaTexto">
                        Digite uma senha
                    </span>

                </div>


                <span
                    class="erro-campo"
                    id="erroSenha"
                ></span>

            </div>


            <!-- =================================================
                 TIPO DE USUÁRIO
                 ================================================= -->

            <div class="campo">

                <label>
                    Tipo de usuário
                </label>


                <div class="tipo-container">


                    <!-- ALUNO -->

                    <label class="tipo-option">

                        <input
                            type="radio"
                            name="tipo"
                            value="aluno"
                            <?= ($_POST['tipo'] ?? 'aluno') === 'aluno' ? 'checked' : '' ?>
                        >

                        <div class="tipo-card">

                            <div class="tipo-icon">
                                🎓
                            </div>

                            <div class="tipo-texto">

                                <strong>
                                    Aluno
                                </strong>

                                <small>
                                    Aprender e jogar
                                </small>

                            </div>

                        </div>

                    </label>


                    <!-- PROFESSOR -->

                    <label class="tipo-option">

                        <input
                            type="radio"
                            name="tipo"
                            value="professor"
                            <?= ($_POST['tipo'] ?? '') === 'professor' ? 'checked' : '' ?>
                        >

                        <div class="tipo-card">

                            <div class="tipo-icon">
                                👨‍🏫
                            </div>

                            <div class="tipo-texto">

                                <strong>
                                    Professor
                                </strong>

                                <small>
                                    Gerenciar alunos
                                </small>

                            </div>

                        </div>

                    </label>


                </div>

            </div>


            <!-- =================================================
                 SÉRIE / TURMA
                 ================================================= -->

            <div
                class="campo"
                id="campo-turma"
            >

                <label for="turma_id">
                    Série / Turma
                </label>


                <select
                    id="turma_id"
                    name="turma_id"
                >

                    <option value="" selected>
                        Selecione série/turma
                    </option>


                    <?php foreach ($turmas as $turma): ?>

                        <option
                            value="<?= (int) $turma['id'] ?>"
                            <?= ((int) ($_POST['turma_id'] ?? 0) === (int) $turma['id']) ? 'selected' : '' ?>
                        >

                            <?= htmlspecialchars($turma['nome']) ?>

                        </option>

                    <?php endforeach; ?>


                </select>


                <span
                    class="erro-campo"
                    id="erroTurma"
                ></span>

            </div>


            <!-- BOTÃO -->

            <button
                type="submit"
                class="btn-cadastrar"
            >

                <span>
                    Criar minha conta
                </span>

                <span class="btn-seta">
                    →
                </span>

            </button>


        </form>


        <!-- LOGIN -->

        <div class="login-link">

            Já possui uma conta?

            <a href="login.php">
                Entrar
            </a>

        </div>


    </main>


    <!-- =====================================================
         LADO DIREITO
         ===================================================== -->

    <aside class="cadastro-lateral">


        <div class="lateral-content">


            <span class="lateral-badge">
                🚀 Comece agora
            </span>


            <h2>
                Aprenda matemática
                de um jeito diferente.
            </h2>


            <p>
                No MathPlay, você aprende matemática
                enquanto joga, conquista medalhas
                e acompanha sua evolução.
            </p>


            <div class="beneficios">


                <div class="beneficio">

                    <span>
                        🎮
                    </span>

                    <div>

                        <strong>
                            Aprenda jogando
                        </strong>

                        <small>
                            Desafios matemáticos interativos
                        </small>

                    </div>

                </div>


                <div class="beneficio">

                    <span>
                        🏆
                    </span>

                    <div>

                        <strong>
                            Conquiste medalhas
                        </strong>

                        <small>
                            Evolua e desbloqueie conquistas
                        </small>

                    </div>

                </div>


                <div class="beneficio">

                    <span>
                        📊
                    </span>

                    <div>

                        <strong>
                            Acompanhe seu progresso
                        </strong>

                        <small>
                            Veja sua evolução na matemática
                        </small>

                    </div>

                </div>


            </div>


        </div>


    </aside>


</div>


<script>

/*
|--------------------------------------------------------------------------
| MOSTRAR / OCULTAR SENHA
|--------------------------------------------------------------------------
*/

const senha = document.getElementById('senha');
const mostrarSenha = document.getElementById('mostrarSenha');

mostrarSenha.addEventListener('click', function () {

    if (senha.type === 'password') {

        senha.type = 'text';

        mostrarSenha.textContent = '🙈';

    } else {

        senha.type = 'password';

        mostrarSenha.textContent = '👁️';

    }

});


/*
|--------------------------------------------------------------------------
| FORÇA DA SENHA
|--------------------------------------------------------------------------
*/

const forcaBarra = document.getElementById('forcaBarra');
const forcaTexto = document.getElementById('forcaTexto');

senha.addEventListener('input', function () {

    const valor = senha.value;

    let forca = 0;


    if (valor.length >= 6) {
        forca++;
    }

    if (valor.length >= 8) {
        forca++;
    }

    if (/[A-Z]/.test(valor)) {
        forca++;
    }

    if (/[0-9]/.test(valor)) {
        forca++;
    }

    if (/[^A-Za-z0-9]/.test(valor)) {
        forca++;
    }


    if (valor.length === 0) {

        forcaBarra.style.width = '0%';

        forcaTexto.textContent = 'Digite uma senha';

    } else if (forca <= 2) {

        forcaBarra.style.width = '35%';

        forcaTexto.textContent = 'Senha fraca';

    } else if (forca <= 4) {

        forcaBarra.style.width = '70%';

        forcaTexto.textContent = 'Senha média';

    } else {

        forcaBarra.style.width = '100%';

        forcaTexto.textContent = 'Senha forte';

    }

});


/*
|--------------------------------------------------------------------------
| MOSTRAR / OCULTAR TURMA
|--------------------------------------------------------------------------
*/

const tipos = document.querySelectorAll(
    'input[name="tipo"]'
);

const campoTurma = document.getElementById(
    'campo-turma'
);

const turma = document.getElementById(
    'turma_id'
);


function atualizarTurma() {

    const tipoSelecionado =
        document.querySelector(
            'input[name="tipo"]:checked'
        );


    if (!tipoSelecionado) {
        return;
    }


    if (tipoSelecionado.value === 'aluno') {

        campoTurma.style.display = 'block';

        turma.required = true;

    } else {

        campoTurma.style.display = 'none';

        turma.required = false;

        turma.value = '';

    }

}


tipos.forEach(function (radio) {

    radio.addEventListener(
        'change',
        atualizarTurma
    );

});


document.addEventListener(
    'DOMContentLoaded',
    atualizarTurma
);


/*
|--------------------------------------------------------------------------
| VALIDAÇÃO BÁSICA
|--------------------------------------------------------------------------
*/

document.getElementById('formCadastro')
.addEventListener('submit', function (event) {

    let valido = true;


    const nome = document.getElementById('nome');
    const email = document.getElementById('email');
    const senhaValor = document.getElementById('senha').value;


    const tipoSelecionado =
        document.querySelector(
            'input[name="tipo"]:checked'
        );


    /*
    | Nome
    */

    if (nome.value.trim().length < 2) {

        document.getElementById(
            'erroNome'
        ).textContent =
            'Digite seu nome completo.';

        valido = false;

    } else {

        document.getElementById(
            'erroNome'
        ).textContent = '';

    }


    /*
    | E-mail
    */

    if (!email.validity.valid) {

        document.getElementById(
            'erroEmail'
        ).textContent =
            'Digite um e-mail válido.';

        valido = false;

    } else {

        document.getElementById(
            'erroEmail'
        ).textContent = '';

    }


    /*
    | Senha
    */

    if (senhaValor.length < 6) {

        document.getElementById(
            'erroSenha'
        ).textContent =
            'A senha precisa ter pelo menos 6 caracteres.';

        valido = false;

    } else {

        document.getElementById(
            'erroSenha'
        ).textContent = '';

    }


    /*
    | Turma
    */

    if (
        tipoSelecionado &&
        tipoSelecionado.value === 'aluno' &&
        turma.value === ''
    ) {

        document.getElementById(
            'erroTurma'
        ).textContent =
            'Selecione sua série/turma.';

        valido = false;

    } else {

        document.getElementById(
            'erroTurma'
        ).textContent = '';

    }


    if (!valido) {

        event.preventDefault();

    }

});

</script>


</body>

</html>