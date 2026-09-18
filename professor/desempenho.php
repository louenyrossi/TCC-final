<?php
require_once '../includes/auth.php';
require_once '../config/config.php';

protegerPagina(['professor', 'admin']);

$usuarioProfessorId = usuarioId();

/*
|--------------------------------------------------------------------------
| Turmas disponíveis para o professor
|--------------------------------------------------------------------------
*/

if (ehAdmin()) {

    $stmt = $pdo->query("
        SELECT id, nome, ano_serie
        FROM turmas
        WHERE ativo = 1
        ORDER BY ano_serie, nome
    ");

    $turmas = $stmt->fetchAll();

} else {

    $stmt = $pdo->prepare("
        SELECT t.id, t.nome, t.ano_serie
        FROM turmas t
        INNER JOIN professor_turmas pt
            ON pt.turma_id = t.id
        WHERE pt.professor_id = ?
          AND t.ativo = 1
        ORDER BY t.ano_serie, t.nome
    ");

    $stmt->execute([$usuarioProfessorId]);
    $turmas = $stmt->fetchAll();
}

/*
|--------------------------------------------------------------------------
| Jogos disponíveis
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT id, nome
    FROM jogos
    WHERE ativo = 1
    ORDER BY id
");

$jogos = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Filtros
|--------------------------------------------------------------------------
*/

$turmaSelecionada = filter_input(
    INPUT_GET,
    'turma',
    FILTER_VALIDATE_INT
);

$jogoSelecionado = filter_input(
    INPUT_GET,
    'jogo',
    FILTER_VALIDATE_INT
);

/*
|--------------------------------------------------------------------------
| Validação da turma
|--------------------------------------------------------------------------
*/

$turmaPermitida = true;

if ($turmaSelecionada) {

    if (ehAdmin()) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM turmas
            WHERE id = ?
              AND ativo = 1
            LIMIT 1
        ");

        $stmt->execute([$turmaSelecionada]);

    } else {

        $stmt = $pdo->prepare("
            SELECT t.id
            FROM turmas t
            INNER JOIN professor_turmas pt
                ON pt.turma_id = t.id
            WHERE t.id = ?
              AND pt.professor_id = ?
              AND t.ativo = 1
            LIMIT 1
        ");

        $stmt->execute([
            $turmaSelecionada,
            $usuarioProfessorId
        ]);
    }

    $turmaPermitida = (bool) $stmt->fetchColumn();
}

if (!$turmaPermitida) {
    $turmaSelecionada = null;
}

/*
|--------------------------------------------------------------------------
| Monta filtros SQL
|--------------------------------------------------------------------------
*/

$where = [
    "u.tipo = 'aluno'"
];

$params = [];

if (ehProfessor()) {

    $where[] = "
        EXISTS (
            SELECT 1
            FROM professor_turmas pt
            WHERE pt.professor_id = ?
              AND pt.turma_id = u.turma_id
        )
    ";

    $params[] = $usuarioProfessorId;
}

if ($turmaSelecionada) {
    $where[] = "u.turma_id = ?";
    $params[] = $turmaSelecionada;
}

if ($jogoSelecionado) {
    $where[] = "p.jogo_id = ?";
    $params[] = $jogoSelecionado;
}

$whereSql = implode(' AND ', $where);

/*
|--------------------------------------------------------------------------
| Desempenho dos alunos
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        u.id,
        u.nome,
        u.nivel,
        u.xp,
        t.nome AS turma,
        t.ano_serie,

        COUNT(p.id) AS partidas,

        COALESCE(SUM(p.acertos), 0) AS acertos,

        COALESCE(SUM(p.erros), 0) AS erros,

        COALESCE(SUM(p.pontuacao), 0) AS pontuacao

    FROM usuarios u

    LEFT JOIN turmas t
        ON t.id = u.turma_id

    LEFT JOIN partidas p
        ON p.usuario_id = u.id

    WHERE {$whereSql}

    GROUP BY
        u.id,
        u.nome,
        u.nivel,
        u.xp,
        t.nome,
        t.ano_serie

    ORDER BY
        t.ano_serie,
        t.nome,
        u.nome
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$alunos = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Estatísticas gerais
|--------------------------------------------------------------------------
*/

$totalAlunos = count($alunos);
$totalPartidas = 0;
$totalAcertos = 0;
$totalErros = 0;
$totalPontuacao = 0;

foreach ($alunos as $aluno) {

    $totalPartidas += (int) $aluno['partidas'];
    $totalAcertos += (int) $aluno['acertos'];
    $totalErros += (int) $aluno['erros'];
    $totalPontuacao += (int) $aluno['pontuacao'];
}

$totalRespostas = $totalAcertos + $totalErros;

$precisaoGeral = $totalRespostas > 0
    ? round(($totalAcertos / $totalRespostas) * 100)
    : 0;

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Desempenho - MathPlay</title>

    <link
        rel="stylesheet"
        href="../assets/css/prof-desempenho.css"
    >

</head>

<body>

<div class="app">

    <!-- SIDEBAR -->

    <aside class="sidebar" id="sidebar">

        <div class="sidebar-header">

            <div class="logo">

                <span class="logo-icon">M</span>

                <div>
                    <strong>MathPlay</strong>
                    <small>Professor</small>
                </div>

            </div>

        </div>

        <nav class="sidebar-nav">

            <a href="dashboard.php" class="nav-item">
                <span class="nav-icon">▦</span>
                <span>Dashboard</span>
            </a>

            <a href="turmas.php" class="nav-item">
                <span class="nav-icon">👥</span>
                <span>Turmas</span>
            </a>

            <a href="alunos.php" class="nav-item">
                <span class="nav-icon">🎓</span>
                <span>Alunos</span>
            </a>

            <a href="desempenho.php" class="nav-item active">
                <span class="nav-icon">📊</span>
                <span>Desempenho</span>
            </a>

            <a href="relatorios.php" class="nav-item">
                <span class="nav-icon">📄</span>
                <span>Relatórios</span>
            </a>

        </nav>

        <div class="sidebar-bottom">

            <a href="../logout.php" class="logout-link">

                <span>↪</span>
                <span>Sair</span>

            </a>

        </div>

    </aside>

    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
    ></div>

    <!-- CONTEÚDO -->

    <main class="main-content">

        <header class="topbar">

            <button
                type="button"
                class="menu-button"
                id="menuButton"
                aria-label="Abrir menu"
            >
                ☰
            </button>

            <div class="topbar-title">

                <span>Professor</span>

                <strong>Desempenho</strong>

            </div>

        </header>

        <section class="content">

            <!-- CABEÇALHO -->

            <div class="page-header">

                <div>

                    <span class="eyebrow">
                        Acompanhamento
                    </span>

                    <h1>
                        Desempenho dos alunos
                    </h1>

                    <p>
                        Acompanhe os resultados dos estudantes
                        nas atividades do MathPlay.
                    </p>

                </div>

            </div>

            <!-- FILTROS -->

            <form
                method="GET"
                class="filters"
            >

                <div class="filter-group">

                    <label for="turma">
                        Turma
                    </label>

                    <select
                        name="turma"
                        id="turma"
                    >

                        <option value="">
                            Todas as turmas
                        </option>

                        <?php foreach ($turmas as $turma): ?>

                            <option
                                value="<?= (int) $turma['id'] ?>"
                                <?= $turmaSelecionada == $turma['id'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($turma['nome']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="filter-group">

                    <label for="jogo">
                        Jogo
                    </label>

                    <select
                        name="jogo"
                        id="jogo"
                    >

                        <option value="">
                            Todos os jogos
                        </option>

                        <?php foreach ($jogos as $jogo): ?>

                            <option
                                value="<?= (int) $jogo['id'] ?>"
                                <?= $jogoSelecionado == $jogo['id'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($jogo['nome']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="filter-actions">

                    <button
                        type="submit"
                        class="filter-button"
                    >
                        Filtrar
                    </button>

                    <a
                        href="desempenho.php"
                        class="clear-button"
                    >
                        Limpar
                    </a>

                </div>

            </form>

            <!-- RESUMO -->

            <section class="stats-grid">

                <article class="stat-card">

                    <div class="stat-icon">
                        🎓
                    </div>

                    <div>

                        <span>Alunos</span>

                        <strong>
                            <?= $totalAlunos ?>
                        </strong>

                    </div>

                </article>

                <article class="stat-card">

                    <div class="stat-icon">
                        🎮
                    </div>

                    <div>

                        <span>Partidas</span>

                        <strong>
                            <?= $totalPartidas ?>
                        </strong>

                    </div>

                </article>

                <article class="stat-card">

                    <div class="stat-icon">
                        ✅
                    </div>

                    <div>

                        <span>Acertos</span>

                        <strong>
                            <?= $totalAcertos ?>
                        </strong>

                    </div>

                </article>

                <article class="stat-card">

                    <div class="stat-icon">
                        🎯
                    </div>

                    <div>

                        <span>Precisão geral</span>

                        <strong>
                            <?= $precisaoGeral ?>%
                        </strong>

                    </div>

                </article>

                <article class="stat-card">

                    <div class="stat-icon">
                        ⭐
                    </div>

                    <div>

                        <span>Pontuação</span>

                        <strong>
                            <?= number_format(
                                $totalPontuacao,
                                0,
                                ',',
                                '.'
                            ) ?>
                        </strong>

                    </div>

                </article>

            </section>

            <!-- LISTA -->

            <section class="panel">

                <div class="panel-header">

                    <div>

                        <h2>
                            Resultado por aluno
                        </h2>

                        <p>
                            <?= $totalAlunos ?>
                            aluno(s) encontrado(s).
                        </p>

                    </div>

                </div>

                <?php if (empty($alunos)): ?>

                    <div class="empty-state">

                        <span>📊</span>

                        <strong>
                            Nenhum aluno encontrado
                        </strong>

                        <p>
                            Tente alterar os filtros selecionados.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="table-wrapper">

                        <table>

                            <thead>

                                <tr>

                                    <th>Aluno</th>

                                    <th>Turma</th>

                                    <th>Partidas</th>

                                    <th>Acertos</th>

                                    <th>Erros</th>

                                    <th>Precisão</th>

                                    <th>Pontuação</th>

                                    <th></th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach ($alunos as $aluno): ?>

                                <?php

                                $acertosAluno =
                                    (int) $aluno['acertos'];

                                $errosAluno =
                                    (int) $aluno['erros'];

                                $totalAluno =
                                    $acertosAluno + $errosAluno;

                                $precisaoAluno =
                                    $totalAluno > 0
                                        ? round(
                                            ($acertosAluno / $totalAluno)
                                            * 100
                                        )
                                        : 0;

                                ?>

                                <tr>

                                    <td>

                                        <div class="student-cell">

                                            <div class="student-avatar">

                                                <?= htmlspecialchars(
                                                    mb_strtoupper(
                                                        mb_substr(
                                                            $aluno['nome'],
                                                            0,
                                                            1
                                                        )
                                                    )
                                                ) ?>

                                            </div>

                                            <div>

                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $aluno['nome']
                                                    ) ?>
                                                </strong>

                                                <span>
                                                    Nível
                                                    <?= (int) $aluno['nivel'] ?>
                                                </span>

                                            </div>

                                        </div>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $aluno['turma']
                                            ?? 'Sem turma'
                                        ) ?>

                                    </td>

                                    <td>
                                        <?= (int) $aluno['partidas'] ?>
                                    </td>

                                    <td>

                                        <span class="correct">
                                            <?= $acertosAluno ?>
                                        </span>

                                    </td>

                                    <td>

                                        <span class="wrong">
                                            <?= $errosAluno ?>
                                        </span>

                                    </td>

                                    <td>

                                        <div class="accuracy-cell">

                                            <strong>
                                                <?= $precisaoAluno ?>%
                                            </strong>

                                            <div class="accuracy-bar">

                                                <div
                                                    class="accuracy-fill"
                                                    style="width: <?= $precisaoAluno ?>%;"
                                                ></div>

                                            </div>

                                        </div>

                                    </td>

                                    <td>

                                        <strong>
                                            <?= number_format(
                                                (int) $aluno['pontuacao'],
                                                0,
                                                ',',
                                                '.'
                                            ) ?>
                                        </strong>

                                    </td>

                                    <td>

                                        <a
                                            href="aluno.php?id=<?= (int) $aluno['id'] ?>"
                                            class="view-button"
                                        >
                                            Ver
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </section>

        </section>

    </main>

</div>

<script src="../assets/js/prof-desempenho.js"></script>

</body>
</html>