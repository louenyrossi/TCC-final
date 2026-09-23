<?php

require_once '../includes/auth.php';
require_once '../config/config.php';

protegerPagina(['professor', 'admin']);

$usuarioId = usuarioId();

/*
|--------------------------------------------------------------------------
| Buscar todas as turmas
|--------------------------------------------------------------------------
*/

$stmtTurmas = $pdo->query("
    SELECT
        id,
        nome,
        ano_serie
    FROM turmas
    WHERE ativo = 1
    ORDER BY ano_serie ASC, nome ASC
");

$turmasDisponiveis = $stmtTurmas->fetchAll();

/*
|--------------------------------------------------------------------------
| Buscar alunos
|--------------------------------------------------------------------------
*/

if (ehAdmin()) {

    $stmt = $pdo->query("
        SELECT
            u.id,
            u.nome,
            u.email,
            u.nivel,
            u.xp,
            t.nome AS turma,
            t.ano_serie
        FROM usuarios u
        LEFT JOIN turmas t
            ON t.id = u.turma_id
        WHERE u.tipo = 'aluno'
        ORDER BY
            t.ano_serie ASC,
            t.nome ASC,
            u.nome ASC
    ");

} else {

    $stmt = $pdo->query("
        SELECT
            u.id,
            u.nome,
            u.email,
            u.nivel,
            u.xp,
            t.nome AS turma,
            t.ano_serie
        FROM usuarios u
        LEFT JOIN turmas t
            ON t.id = u.turma_id
        WHERE u.tipo = 'aluno'
        ORDER BY
            t.ano_serie ASC,
            t.nome ASC,
            u.nome ASC
    ");
}

$alunos = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Estatísticas
|--------------------------------------------------------------------------
*/

$totalAlunos = count($alunos);

$totalTurmas = count($turmasDisponiveis);

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Alunos | MathPlay</title>

    <link
        rel="stylesheet"
        href="../assets/css/prof-alunos.css"
    >

</head>

<body>

<div class="app">

    <!-- SIDEBAR -->

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
                <span>🏠</span>
                <span>Dashboard</span>
            </a>


            <a
                href="turmas.php"
                class="nav-item"
            >
                <span>🏫</span>
                <span>Turmas</span>
            </a>


            <a
                href="alunos.php"
                class="nav-item active"
            >
                <span>👨‍🎓</span>
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


    <!-- MAIN -->

    <main class="main-content">

        <header class="topbar">

            <button
                type="button"
                class="menu-button"
                id="menu-button"
                aria-label="Abrir menu"
            >
                ☰
            </button>

            <strong>
                Alunos
            </strong>

        </header>


        <section class="content">

            <!-- HEADER -->

            <div class="page-header">

                <div>

                    <span class="eyebrow">
                        ÁREA DO PROFESSOR
                    </span>

                    <h1>
                        Meus alunos
                    </h1>

                    <p>
                        Consulte os alunos das suas turmas.
                    </p>

                </div>

            </div>


            <!-- RESUMO -->

            <section class="summary-grid">

                <div class="summary-card">

                    <div class="summary-icon">
                        👨‍🎓
                    </div>

                    <div>

                        <span>
                            Total de alunos
                        </span>

                        <strong>
                            <?= $totalAlunos ?>
                        </strong>

                    </div>

                </div>


                <div class="summary-card">

                    <div class="summary-icon">
                        🏫
                    </div>

                    <div>

                        <span>
                            Turmas
                        </span>

                        <strong>
                            <?= $totalTurmas ?>
                        </strong>

                    </div>

                </div>

            </section>


            <!-- FILTRO -->

            <section class="filters-card">

                <div class="search-wrapper">

                    <span>
                        🔎
                    </span>

                    <input
                        type="text"
                        id="search-aluno"
                        placeholder="Buscar aluno..."
                        autocomplete="off"
                    >

                </div>


                <select id="filter-turma">

                    <option value="">
                        Todas as turmas
                    </option>

                    <?php foreach ($turmasDisponiveis as $turma): ?>

                        <option
                            value="<?= htmlspecialchars($turma['nome']) ?>"
                        >
                            <?= htmlspecialchars($turma['nome']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </section>


            <!-- LISTA -->

            <section class="students-section">

                <div class="section-header">

                    <div>

                        <h2>
                            Lista de alunos
                        </h2>

                        <p id="results-count">
                            <?= $totalAlunos ?> aluno(s) encontrado(s)
                        </p>

                    </div>

                </div>


                <?php if (!$alunos): ?>

                    <div class="empty-card">

                        <div>
                            👨‍🎓
                        </div>

                        <h2>
                            Nenhum aluno encontrado
                        </h2>

                        <p>
                            Os alunos cadastrados aparecerão aqui.
                        </p>

                    </div>

                <?php else: ?>

                    <?php
                    /*
                    |--------------------------------------------------------------------------
                    | Agrupar alunos por turma
                    |--------------------------------------------------------------------------
                    */

                    $alunosPorTurma = [];

                    foreach ($alunos as $aluno) {

                        $turmaNome = !empty($aluno['turma'])
                            ? $aluno['turma']
                            : 'Sem turma';

                        $alunosPorTurma[$turmaNome][] = $aluno;
                    }
                    ?>


                    <div
                        class="students-list"
                        id="students-list"
                    >

                        <?php foreach ($turmasDisponiveis as $turma): ?>

                            <?php

                            $nomeTurma = $turma['nome'];

                            if (empty($alunosPorTurma[$nomeTurma])) {
                                continue;
                            }

                            $alunosDaTurma = $alunosPorTurma[$nomeTurma];

                            /*
                            | Ordenação alfabética dos alunos
                            */

                            usort(
                                $alunosDaTurma,
                                function ($a, $b) {
                                    return strcasecmp(
                                        $a['nome'],
                                        $b['nome']
                                    );
                                }
                            );

                            ?>

                            <!-- GRUPO DA TURMA -->

                            <div
                                class="turma-group"
                                data-turma="<?= htmlspecialchars($nomeTurma) ?>"
                            >

                                <div class="turma-group-header">

                                    <div>

                                        <span class="turma-label">
                                            TURMA
                                        </span>

                                        <h3>
                                            <?= htmlspecialchars($nomeTurma) ?>
                                        </h3>

                                    </div>

                                    <span class="turma-total">
                                        <?= count($alunosDaTurma) ?>
                                        aluno(s)
                                    </span>

                                </div>


                                <div class="turma-students">

                                    <?php foreach ($alunosDaTurma as $aluno): ?>

                                        <?php

                                        $inicial = mb_strtoupper(
                                            mb_substr(
                                                $aluno['nome'],
                                                0,
                                                1
                                            )
                                        );

                                        ?>

                                        <a
                                            href="aluno.php?id=<?= (int) $aluno['id'] ?>"
                                            class="student-card"
                                            data-name="<?= htmlspecialchars(
                                                mb_strtolower($aluno['nome'])
                                            ) ?>"
                                            data-turma="<?= htmlspecialchars(
                                                $aluno['turma'] ?? ''
                                            ) ?>"
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
                                                    <?= htmlspecialchars(
                                                        $aluno['turma'] ?? 'Sem turma'
                                                    ) ?>
                                                </span>

                                                <small>
                                                    Nível
                                                    <?= (int) $aluno['nivel'] ?>

                                                    ·

                                                    <?= (int) $aluno['xp'] ?>
                                                    XP
                                                </small>

                                            </div>


                                            <div class="student-arrow">
                                                →
                                            </div>

                                        </a>

                                    <?php endforeach; ?>

                                </div>

                            </div>

                        <?php endforeach; ?>


                        <!-- ALUNOS SEM TURMA -->

                        <?php if (!empty($alunosPorTurma['Sem turma'])): ?>

                            <?php

                            $alunosSemTurma =
                                $alunosPorTurma['Sem turma'];

                            usort(
                                $alunosSemTurma,
                                function ($a, $b) {
                                    return strcasecmp(
                                        $a['nome'],
                                        $b['nome']
                                    );
                                }
                            );

                            ?>

                            <div
                                class="turma-group"
                                data-turma="Sem turma"
                            >

                                <div class="turma-group-header">

                                    <div>

                                        <span class="turma-label">
                                            TURMA
                                        </span>

                                        <h3>
                                            Sem turma
                                        </h3>

                                    </div>

                                    <span class="turma-total">
                                        <?= count($alunosSemTurma) ?>
                                        aluno(s)
                                    </span>

                                </div>


                                <div class="turma-students">

                                    <?php foreach ($alunosSemTurma as $aluno): ?>

                                        <?php

                                        $inicial = mb_strtoupper(
                                            mb_substr(
                                                $aluno['nome'],
                                                0,
                                                1
                                            )
                                        );

                                        ?>

                                        <a
                                            href="aluno.php?id=<?= (int) $aluno['id'] ?>"
                                            class="student-card"
                                            data-name="<?= htmlspecialchars(
                                                mb_strtolower($aluno['nome'])
                                            ) ?>"
                                            data-turma=""
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
                                                    Sem turma
                                                </span>

                                                <small>
                                                    Nível
                                                    <?= (int) $aluno['nivel'] ?>

                                                    ·

                                                    <?= (int) $aluno['xp'] ?>
                                                    XP
                                                </small>

                                            </div>


                                            <div class="student-arrow">
                                                →
                                            </div>

                                        </a>

                                    <?php endforeach; ?>

                                </div>

                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- SEM RESULTADOS DO FILTRO -->

                    <div
                        class="no-results"
                        id="no-results"
                        hidden
                    >

                        <div>
                            🔎
                        </div>

                        <h3>
                            Nenhum aluno encontrado
                        </h3>

                        <p>
                            Tente alterar o nome ou a turma selecionada.
                        </p>

                    </div>

                <?php endif; ?>

            </section>

        </section>

    </main>

</div>


<div
    class="sidebar-overlay"
    id="sidebar-overlay"
></div>


<script src="../assets/js/prof-alunos.js"></script>

</body>

</html>