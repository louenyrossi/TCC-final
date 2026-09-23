<?php
require_once '../includes/auth.php';
require_once '../config/config.php';

protegerPagina(['professor', 'admin']);

$usuarioId = usuarioId();

/*
|--------------------------------------------------------------------------
| Todas as turmas cadastradas
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
    GROUP BY t.id, t.nome, t.ano_serie
    ORDER BY t.ano_serie ASC, t.nome ASC
");

$turmas = $stmt->fetchAll();

$turmaSelecionada = null;
$alunos = [];

if (isset($_GET['id'])) {

    $turmaId = (int) $_GET['id'];

    if (ehAdmin()) {

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
            ORDER BY nome
        ");

        $stmt->execute([$turmaId]);

    } else {

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
}

    $alunos = $stmt->fetchAll();

    foreach ($turmas as $turma) {

        if ((int) $turma['id'] === $turmaId) {
            $turmaSelecionada = $turma;
            break;
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

    <title>Turmas | MathPlay</title>

    <link
        rel="stylesheet"
        href="../assets/css/prof-turmas.css"
    >

</head>

<body>

<div class="app">

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
                <span>🚪</span>
                <span>Sair</span>
            </a>

        </div>

    </aside>


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
                Turmas
            </strong>

        </header>


        <section class="content">

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


            <?php if (!$turmas): ?>

                <div class="empty-card">

                    <div class="empty-icon">
                        🏫
                    </div>

                    <h2>
                        Nenhuma turma encontrada
                    </h2>

                    <p>
                        As turmas vinculadas ao professor aparecerão aqui.
                    </p>

                </div>

            <?php else: ?>

                <div class="classes-grid">

                    <?php foreach ($turmas as $turma): ?>

                        <a
                            href="turmas.php?id=<?= (int) $turma['id'] ?>"
                            class="class-card
                            <?= $turmaSelecionada &&
                                (int) $turmaSelecionada['id'] === (int) $turma['id']
                                ? 'selected'
                                : '' ?>"
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
                                <?= htmlspecialchars($turma['nome']) ?>
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


                <?php if ($turmaSelecionada): ?>

                    <section class="students-section">

                        <div class="section-header">

                            <div>

                                <span class="eyebrow">
                                    TURMA SELECIONADA
                                </span>

                                <h2>
                                    <?= htmlspecialchars(
                                        $turmaSelecionada['nome']
                                    ) ?>
                                </h2>

                                <p>
                                    <?= (int) $turmaSelecionada['total_alunos'] ?>
                                    aluno(s) nesta turma.
                                </p>

                            </div>

                        </div>


                        <?php if (!$alunos): ?>

                            <div class="empty-card small">

                                <div class="empty-icon">
                                    👨‍🎓
                                </div>

                                <h2>
                                    Nenhum aluno
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
                                        mb_substr($aluno['nome'], 0, 1)
                                    );
                                    ?>

                                    <a
                                        href="aluno.php?id=<?= (int) $aluno['id'] ?>"
                                        class="student-card"
                                    >

                                        <div class="student-avatar">
                                            <?= htmlspecialchars($inicial) ?>
                                        </div>


                                        <div class="student-info">

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $aluno['nome']
                                                ) ?>
                                            </strong>

                                            <span>
                                                Nível <?= (int) $aluno['nivel'] ?>
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

                <?php endif; ?>

            <?php endif; ?>

        </section>

    </main>

</div>


<div
    class="sidebar-overlay"
    id="sidebar-overlay"
></div>


<script src="../assets/js/prof-turmas.js"></script>

</body>

</html>