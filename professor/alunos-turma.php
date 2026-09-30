<?php

require_once '../includes/auth.php';
require_once '../config/config.php';

protegerPagina(['professor', 'admin']);

/*
|--------------------------------------------------------------------------
| PEGAR ID DA TURMA
|--------------------------------------------------------------------------
*/

$turmaId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($turmaId <= 0) {
    header('Location: turmas.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| BUSCAR TURMA
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        nome,
        ano_serie
    FROM turmas
    WHERE id = ?
      AND ativo = 1
    LIMIT 1
");

$stmt->execute([$turmaId]);

$turma = $stmt->fetch();

if (!$turma) {
    header('Location: turmas.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| BUSCAR ALUNOS DA TURMA
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        nome,
        email,
        nivel,
        xp
    FROM usuarios
    WHERE turma_id = ?
      AND tipo = 'aluno'
    ORDER BY nome ASC
");

$stmt->execute([$turmaId]);

$alunos = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars($turma['nome']) ?> | MathPlay
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/prof-turmas.css"
    >

</head>

<body>

<div class="app">

    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar">

        <div class="sidebar-header">

            <div class="logo">

                <span class="logo-icon">
                    M
                </span>

                <div>

                    <strong>
                        MathPlay
                    </strong>

                    <small>
                        Área do Professor
                    </small>

                </div>

            </div>

        </div>


        <nav class="sidebar-nav">

            <a
                href="dashboard.php"
                class="nav-item"
            >
                <span>▦</span>
                <span>Dashboard</span>
            </a>


            <a
                href="turmas.php"
                class="nav-item active"
            >
                <span>👥</span>
                <span>Turmas</span>
            </a>


            <a
                href="alunos.php"
                class="nav-item"
            >
                <span>🎓</span>
                <span>Alunos</span>
            </a>


            <a
                href="desempenho.php"
                class="nav-item"
            >
                <span>📊</span>
                <span>Desempenho</span>
            </a>


            <a
                href="relatorios.php"
                class="nav-item"
            >
                <span>📄</span>
                <span>Relatórios</span>
            </a>

        </nav>


        <div class="sidebar-bottom">

            <a
                href="../logout.php"
                class="logout-link"
            >
                <span>↪</span>
                <span>Sair</span>
            </a>

        </div>

    </aside>


    <!-- =====================================================
         CONTEÚDO
    ====================================================== -->

    <main class="main-content">

        <header class="topbar">

            <button
                type="button"
                class="menu-button"
                id="menu-button"
            >
                ☰
            </button>


            <strong>
                Alunos da turma
            </strong>

        </header>


        <section class="content">

            <!-- CABEÇALHO -->

            <div class="page-header">

                <div>

                    <a
                        href="turmas.php"
                        style="
                            display:inline-block;
                            margin-bottom:16px;
                            text-decoration:none;
                        "
                    >
                        ← Voltar para turmas
                    </a>


                    <span class="eyebrow">
                        TURMA
                    </span>


                    <h1>

                        <?= htmlspecialchars(
                            $turma['nome'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </h1>


                    <p>

                        <?= (int) $turma['ano_serie'] ?>º ano

                        ·

                        <?= count($alunos) ?> aluno(s)

                    </p>

                </div>

            </div>


            <!-- =================================================
                 LISTA DE ALUNOS
            ================================================== -->

            <?php if (empty($alunos)): ?>

                <div class="empty-card">

                    <div class="empty-icon">
                        👨‍🎓
                    </div>


                    <h2>
                        Nenhum aluno encontrado
                    </h2>


                    <p>
                        Esta turma ainda não possui alunos cadastrados.
                    </p>

                </div>

            <?php else: ?>

                <div class="students-grid">

                    <?php foreach ($alunos as $aluno): ?>

                        <?php

                        $inicial = mb_strtoupper(
                            mb_substr(
                                $aluno['nome'],
                                0,
                                1,
                                'UTF-8'
                            ),
                            'UTF-8'
                        );

                        ?>

                        <a
                            href="aluno.php?id=<?= (int) $aluno['id'] ?>"
                            class="student-card"
                        >

                            <div class="student-avatar">

                                <?= htmlspecialchars(
                                    $inicial,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </div>


                            <div class="student-info">

                                <strong>

                                    <?= htmlspecialchars(
                                        $aluno['nome'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </strong>


                                <span>

                                    Nível
                                    <?= (int) $aluno['nivel'] ?>

                                </span>


                                <small>

                                    <?= (int) $aluno['xp'] ?> XP

                                </small>

                            </div>


                            <div class="student-arrow">
                                →
                            </div>

                        </a>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>


<!-- MENU MOBILE -->

<div
    class="sidebar-overlay"
    id="sidebar-overlay"
></div>


<script src="../assets/js/prof-turmas.js"></script>

</body>

</html>