<?php
require_once '../includes/auth.php';
require_once '../config/config.php';

protegerPagina(['professor', 'admin']);

$usuarioProfessorId = usuarioId();

/*
|--------------------------------------------------------------------------
| TURMAS DISPONÍVEIS
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
        SELECT DISTINCT
            t.id,
            t.nome,
            t.ano_serie
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
| JOGOS
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
| FILTROS
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
| VALIDA TURMA
|--------------------------------------------------------------------------
*/

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

    if (!$stmt->fetchColumn()) {
        $turmaSelecionada = null;
    }
}

/*
|--------------------------------------------------------------------------
| VALIDA JOGO
|--------------------------------------------------------------------------
*/

if ($jogoSelecionado) {

    $stmt = $pdo->prepare("
        SELECT id
        FROM jogos
        WHERE id = ?
          AND ativo = 1
        LIMIT 1
    ");

    $stmt->execute([$jogoSelecionado]);

    if (!$stmt->fetchColumn()) {
        $jogoSelecionado = null;
    }
}

/*
|--------------------------------------------------------------------------
| ALUNOS DO RELATÓRIO
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

$whereSql = implode(' AND ', $where);

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
        " . ($jogoSelecionado ? "AND p.jogo_id = ?" : "") . "

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

if ($jogoSelecionado) {
    array_unshift($params, $jogoSelecionado);
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$alunos = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| RESUMO
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

/*
|--------------------------------------------------------------------------
| NOME DO FILTRO
|--------------------------------------------------------------------------
*/

$nomeTurmaRelatorio = 'Todas as turmas';
$nomeJogoRelatorio = 'Todos os jogos';

foreach ($turmas as $turma) {

    if ($turmaSelecionada == $turma['id']) {

        $nomeTurmaRelatorio =
            $turma['ano_serie'] . 'º ano — Turma ' .
            substr($turma['nome'], -1);

        break;
    }
}

foreach ($jogos as $jogo) {

    if ($jogoSelecionado == $jogo['id']) {
        $nomeJogoRelatorio = $jogo['nome'];
        break;
    }
}

$dataRelatorio = date('d/m/Y H:i');

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Relatórios - MathPlay</title>

    <link
        rel="stylesheet"
        href="../assets/css/prof-relatorios.css"
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

            <a href="desempenho.php" class="nav-item">
                <span class="nav-icon">📊</span>
                <span>Desempenho</span>
            </a>

            <a href="relatorios.php" class="nav-item active">
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

    <!-- MAIN -->

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

                <strong>Relatórios</strong>

            </div>

        </header>

        <section class="content">

            <!-- CABEÇALHO -->

            <div class="page-header">

                <div>

                    <span class="eyebrow">
                        Relatórios
                    </span>

                    <h1>
                        Relatório de desempenho
                    </h1>

                    <p>
                        Consulte e organize os resultados dos alunos.
                    </p>

                </div>

                <button
                    type="button"
                    class="print-button"
                    id="printButton"
                >
                    🖨️ Imprimir relatório
                </button>

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

                                <?= (int) $turma['ano_serie'] ?>º ano —
                                Turma <?= htmlspecialchars(
                                    substr($turma['nome'], -1)
                                ) ?>

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
                        Gerar
                    </button>

                    <a
                        href="relatorios.php"
                        class="clear-button"
                    >
                        Limpar
                    </a>

                </div>

            </form>

            <!-- ÁREA DO RELATÓRIO -->

            <section
                class="report-area"
                id="reportArea"
            >

                <div class="report-header">

                    <div>

                        <span class="report-label">
                            MATHPLAY
                        </span>

                        <h2>
                            Relatório de desempenho
                        </h2>

                        <p>
                            Acompanhamento dos estudantes
                        </p>

                    </div>

                    <div class="report-date">

                        <span>Gerado em</span>

                        <strong>
                            <?= $dataRelatorio ?>
                        </strong>

                    </div>

                </div>

                <!-- FILTROS ATUAIS -->

                <div class="report-filters">

                    <div>

                        <span>Turma</span>

                        <strong>
                            <?= htmlspecialchars($nomeTurmaRelatorio) ?>
                        </strong>

                    </div>

                    <div>

                        <span>Jogo</span>

                        <strong>
                            <?= htmlspecialchars($nomeJogoRelatorio) ?>
                        </strong>

                    </div>

                </div>

                <!-- RESUMO -->

                <div class="report-stats">

                    <div class="report-stat">

                        <span>Alunos</span>

                        <strong>
                            <?= $totalAlunos ?>
                        </strong>

                    </div>

                    <div class="report-stat">

                        <span>Partidas</span>

                        <strong>
                            <?= $totalPartidas ?>
                        </strong>

                    </div>

                    <div class="report-stat">

                        <span>Acertos</span>

                        <strong>
                            <?= $totalAcertos ?>
                        </strong>

                    </div>

                    <div class="report-stat">

                        <span>Erros</span>

                        <strong>
                            <?= $totalErros ?>
                        </strong>

                    </div>

                    <div class="report-stat">

                        <span>Precisão</span>

                        <strong>
                            <?= $precisaoGeral ?>%
                        </strong>

                    </div>

                    <div class="report-stat">

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

                </div>

                <!-- TABELA -->

                <div class="report-table">

                    <div class="table-title">

                        <div>

                            <h3>
                                Desempenho dos alunos
                            </h3>

                            <p>
                                Resultados registrados no sistema.
                            </p>

                        </div>

                    </div>

                    <?php if (empty($alunos)): ?>

                        <div class="empty-state">

                            <span>📄</span>

                            <strong>
                                Nenhum dado encontrado
                            </strong>

                            <p>
                                Não existem resultados para os filtros
                                selecionados.
                            </p>

                        </div>

                    <?php else: ?>

                        <div class="table-wrapper">

                            <table>

                                <thead>

                                    <tr>

                                        <th>Aluno</th>
                                        <th>Turma</th>
                                        <th>Nível</th>
                                        <th>Partidas</th>
                                        <th>Acertos</th>
                                        <th>Erros</th>
                                        <th>Precisão</th>
                                        <th>Pontuação</th>

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

                                                <strong>
                                                    <?= htmlspecialchars(
                                                        $aluno['nome']
                                                    ) ?>
                                                </strong>

                                            </div>

                                        </td>

                                        <td>

                                            <?= htmlspecialchars(
                                                $aluno['turma']
                                                ?? 'Sem turma'
                                            ) ?>

                                        </td>

                                        <td>

                                            <?= (int) $aluno['nivel'] ?>

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

                                            <strong>
                                                <?= $precisaoAluno ?>%
                                            </strong>

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

                                    </tr>

                                <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php endif; ?>

                </div>

                <div class="report-footer">

                    <span>
                        MathPlay — Plataforma Educacional
                    </span>

                    <span>
                        Relatório gerado pelo sistema
                    </span>

                </div>

            </section>

        </section>

    </main>

</div>

<script src="../assets/js/prof-relatorios.js"></script>

</body>
</html>