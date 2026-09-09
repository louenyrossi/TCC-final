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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $tipo = $_POST['tipo'] ?? 'aluno';
    $turmaId = !empty($_POST['turma_id'])
        ? (int) $_POST['turma_id']
        : null;


    /*
     * ==========================================
     * VALIDAÇÕES
     * ==========================================
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

        $erro = 'Selecione a turma do aluno.';

    }


    /*
     * ==========================================
     * CADASTRO
     * ==========================================
     */

    if ($erro === '') {

        try {

            /*
             * Verifica e-mail
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
                 * Verifica turma
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

                        $erro = 'A turma selecionada não existe.';

                    }

                }


                if ($erro === '') {

                    $senhaHash = password_hash(
                        $senha,
                        PASSWORD_DEFAULT
                    );


                    /*
                     * Cria usuário
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


                    $usuarioId = (int) $pdo->lastInsertId();


                    /*
                     * Cria progresso inicial dos jogos
                     * para alunos
                     */

                    if ($tipo === 'aluno') {

                        $stmt = $pdo->prepare("
                            SELECT id
                            FROM jogos
                            WHERE ativo = 1
                        ");

                        $stmt->execute();

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
                                    'bloqueado',
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

            $erro = 'Erro ao realizar o cadastro: ' . $e->getMessage();

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

<main class="pagina-cadastro">

    <section class="card-cadastro">

        <div class="cabecalho-cadastro">

            <div class="logo-cadastro">
                🧮
            </div>

            <h1>
                Criar conta
            </h1>

            <p>
                Faça seu cadastro no MathPlay
            </p>

        </div>


        <?php if ($erro !== ''): ?>

            <div class="mensagem-erro">

                <?= htmlspecialchars($erro) ?>

            </div>

        <?php endif; ?>


        <?php if ($sucesso !== ''): ?>

            <div class="mensagem-sucesso">

                <?= htmlspecialchars($sucesso) ?>

                <br><br>

                <a href="login.php">
                    Ir para o login
                </a>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action=""
        >


            <div class="campo">

                <label for="nome">
                    Nome
                </label>

                <input
                    type="text"
                    id="nome"
                    name="nome"
                    value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                    placeholder="Digite o nome"
                    required
                >

            </div>


            <div class="campo">

                <label for="email">
                    E-mail
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    placeholder="Digite o e-mail"
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
                    placeholder="Mínimo de 6 caracteres"
                    required
                >

            </div>


            <div class="campo">

                <label for="tipo">
                    Tipo de usuário
                </label>

                <select
                    id="tipo"
                    name="tipo"
                    onchange="mostrarTurma()"
                >

                    <option
                        value="aluno"
                        <?= ($_POST['tipo'] ?? 'aluno') === 'aluno' ? 'selected' : '' ?>
                    >
                        Aluno
                    </option>

                    <option
                        value="professor"
                        <?= ($_POST['tipo'] ?? '') === 'professor' ? 'selected' : '' ?>
                    >
                        Professor
                    </option>

                </select>

            </div>


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

                    <option value="">
                        Selecione sua turma
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

            </div>


            <button
                type="submit"
                class="botao-cadastro"
            >
                Criar conta
            </button>


        </form>


        <p class="link-login">

            Já possui uma conta?

            <a href="login.php">
                Entrar
            </a>

        </p>


    </section>

</main>


<script>

function mostrarTurma() {

    const tipo = document.getElementById('tipo').value;
    const campoTurma = document.getElementById('campo-turma');
    const turma = document.getElementById('turma_id');

    if (tipo === 'aluno') {

        campoTurma.style.display = 'block';
        turma.required = true;

    } else {

        campoTurma.style.display = 'none';
        turma.required = false;
        turma.value = '';

    }

}

document.addEventListener('DOMContentLoaded', mostrarTurma);

</script>

</body>

</html>