<?php

require_once '../includes/auth.php';
require_once '../config/config.php';

protegerPagina(['professor', 'admin']);

$usuarioId = usuarioId();

/*
|--------------------------------------------------------------------------
| BUSCAR TODAS AS TURMAS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        t.id,
        t.nome,
        t.ano_serie,
        COUNT(DISTINCT u.id) AS total_alunos
    FROM turmas t
    LEFT JOIN usuarios u
        ON u.turma_id = t.id
        AND u.tipo = 'aluno'
    WHERE t.ativo = 1
    GROUP BY
        t.id,
        t.nome,
        t.ano_serie
    ORDER BY
        t.ano_serie ASC,
        t.nome ASC
");

$turmas = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| TURMA SELECIONADA
|--------------------------------------------------------------------------
*/

$turmaSelecionada = null;
$alunos = [];

if (isset($_GET['id'])) {

    $turmaId = (int) $_GET['id'];

    /*
    |--------------------------------------------------------------------------
    | ENCONTRAR A TURMA CLICADA
    |--------------------------------------------------------------------------
    */

    foreach ($turmas as $turma) {

        if ((int) $turma['id'] === $turmaId) {

            $turmaSelecionada = $turma;

            break;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BUSCAR OS ALUNOS DA TURMA
    |--------------------------------------------------------------------------
    */

    if ($turmaSelecionada) {

        $stmt = $pdo->prepare("
            SELECT
                u.id,
                u.nome,
                u.email,
                u.nivel,
                u.xp
            FROM usuarios u
            WHERE u.turma_id = ?
              AND u.tipo = 'aluno'
            ORDER BY u.nome ASC
        ");

        $stmt->execute([$turmaId]);

        $alunos = $stmt->fetchAll();
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

    <title>Turmas | MathPlay</title>

    <link
        rel="stylesheet"
        href="../assets/css/prof-turmas.css"
    >

</head>

<body>

<div class="app">

    <!-- =========================================================
         SIDEBAR
    ========================================================== -->

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


    <!-- =========================================================
         CONTEÚDO PRINCIPAL
    ========================================================== -->

    <main class="main-content">

        <!-- TOPBAR -->

        <header class="topbar">

            <button
                type="button"
                class="menu-button"
                id="menu-button"
            >
                ☰
            </button>


            <strong>
                Turmas
            </strong>

        </header>


        <!-- CONTEÚDO -->

        <section class="content">

            <!-- CABEÇALHO -->

            <div class="page-header">

                <div>

                    <span class="eyebrow">
                        ÁREA DO PROFESSOR
                    </span>

                    <h1>
                        Minhas turmas
                    </h1>

                    <p>
                        Selecione uma turma para visualizar seus alunos.
                    </p>

                </div>

            </div>


            <!-- =================================================
                 TURMAS
            ================================================== -->

            <?php if (!$turmas): ?>

                <div class="empty-card">

                    <div class="empty-icon">
                        🏫
                    </div>

                    <h2>
                        Nenhuma turma encontrada
                    </h2>

                    <p>
                        As turmas cadastradas aparecerão aqui.
                    </p>

                </div>

            <?php else: ?>

                <div class="classes-grid">

                    <?php foreach ($turmas as $turma): ?>

                        <?php

                        $turmaSelecionadaClass = '';

                        if (
                            $turmaSelecionada &&
                            (int) $turmaSelecionada['id'] === (int) $turma['id']
                        ) {
                            $turmaSelecionadaClass = 'selected';
                        }

                        ?>

                        <a
                            href="./alunos-turma.php?id=<?= (int) $turma['id'] ?>"
                            class="class-card"
                        >

                            <div class="class-top">

                                <div class="class-icon">
                                    🏫
                                </div>

                                <span class="grade">

                                    <?= (int) $turma['ano_serie'] ?>º ano

                                </span>

                            </div>


                            <h2>

                                <?= htmlspecialchars(
                                    $turma['nome'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </h2>


                            <p class="student-count">

                                👨‍🎓

                                <?= (int) $turma['total_alunos'] ?>

                                aluno(s)

                            </p>


                            <div class="class-footer">

                                Ver alunos →

                            </div>

                        </a>

                    <?php endforeach; ?>

                </div>


                <!-- =================================================
                     ALUNOS DA TURMA SELECIONADA
                ================================================== -->

                <?php if ($turmaSelecionada): ?>

                    <section class="students-section">

                        <div class="section-header">

                            <div>

                                <span class="eyebrow">
                                    TURMA SELECIONADA
                                </span>


                                <h2>

                                    <?= htmlspecialchars(
                                        $turmaSelecionada['nome'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </h2>


                                <p>

                                    <?= count($alunos) ?>

                                    aluno(s) encontrado(s) nesta turma.

                                </p>

                            </div>

                        </div>


                        <!-- =========================================
                             NENHUM ALUNO
                        ========================================== -->

                        <?php if (empty($alunos)): ?>

                            <div class="empty-card small">

                                <div class="empty-icon">
                                    👨‍🎓
                                </div>


                                <h2>
                                    Nenhum aluno
                                </h2>


                                <p>
                                    Esta turma ainda não possui alunos
                                    cadastrados.
                                </p>

                            </div>


                        <!-- =========================================
                             LISTA DE ALUNOS
                        ========================================== -->

                        <?php else: ?>

                            <div class="students-grid">

                                <?php foreach ($alunos as $aluno): ?>

                                    <?php

                                    $nomeAluno = $aluno['nome'] ?? '';

                                    $inicial = '';

                                    if ($nomeAluno !== '') {

                                        $inicial = mb_strtoupper(
                                            mb_substr(
                                                $nomeAluno,
                                                0,
                                                1,
                                                'UTF-8'
                                            ),
                                            'UTF-8'
                                        );

                                    }

                                    ?>

                                    <a
                                        href="aluno.php?id=<?= (int) $aluno['id'] ?>"
                                        class="student-card"
                                    >

                                        <!-- AVATAR -->

                                        <div class="student-avatar">

                                            <?= htmlspecialchars(
                                                $inicial,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </div>


                                        <!-- INFORMAÇÕES -->

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

                                                <?= (int) $aluno['xp'] ?>

                                                XP

                                            </small>

                                        </div>


                                        <!-- SETA -->

                                        <div class="student-arrow">

                                            →

                                        </div>

                                    </a>

                                <?php endforeach; ?>

                            </div>

                        <?php endif; ?>

                    </section>

                <?php endif; ?>

            <?php endif; ?>

        </section>

    </main>

</div>


<!-- =========================================================
     OVERLAY DO MENU MOBILE
========================================================== -->

<div
    class="sidebar-overlay"
    id="sidebar-overlay"
></div>


<!-- =========================================================
     JAVASCRIPT
========================================================== -->

<script src="../assets/js/prof-turmas.js"></script>

</body>

</html>