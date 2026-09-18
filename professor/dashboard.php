<?php
require_once '../includes/auth.php';
require_once '../config/config.php';

protegerPagina(['professor', 'admin']);

$usuarioId = usuarioId();

/*
|--------------------------------------------------------------------------
| Dados do professor
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT
        id,
        nome,
        email,
        tipo
    FROM usuarios
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$usuarioId]);
$professor = $stmt->fetch();

if (!$professor) {
    header('Location: ../login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Turmas do professor
|--------------------------------------------------------------------------
*/
if (ehAdmin()) {

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
        ORDER BY t.ano_serie, t.nome
    ");

} else {

    $stmt = $pdo->prepare("
        SELECT
            t.id,
            t.nome,
            t.ano_serie,
            COUNT(DISTINCT u.id) AS total_alunos
        FROM turmas t

        INNER JOIN professor_turmas pt
            ON pt.turma_id = t.id
            AND pt.professor_id = ?

        LEFT JOIN usuarios u
            ON u.turma_id = t.id
            AND u.tipo = 'aluno'

        WHERE t.ativo = 1

        GROUP BY t.id, t.nome, t.ano_serie

        ORDER BY t.ano_serie, t.nome
    ");

    $stmt->execute([$usuarioId]);
}

$turmas = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Quantidade de alunos
|--------------------------------------------------------------------------
*/
if (ehAdmin()) {

    $stmt = $pdo->query("
        SELECT COUNT(*) AS total
        FROM usuarios
        WHERE tipo = 'aluno'
    ");

} else {

    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT u.id) AS total
        FROM usuarios u
        INNER JOIN professor_turmas pt
            ON pt.turma_id = u.turma_id
        WHERE pt.professor_id = ?
          AND u.tipo = 'aluno'
    ");

    $stmt->execute([$usuarioId]);
}

$totalAlunos = (int) ($stmt->fetch()['total'] ?? 0);

/*
|--------------------------------------------------------------------------
| Total de jogos
|--------------------------------------------------------------------------
*/
$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM jogos
    WHERE ativo = 1
");

$totalJogos = (int) ($stmt->fetch()['total'] ?? 0);

/*
|--------------------------------------------------------------------------
| Partidas realizadas pelos alunos das turmas
|--------------------------------------------------------------------------
*/
if (ehAdmin()) {

    $stmt = $pdo->query("
        SELECT
            COUNT(DISTINCT p.id) AS partidas,
            COALESCE(SUM(p.acertos), 0) AS acertos,
            COALESCE(SUM(p.erros), 0) AS erros,
            COALESCE(SUM(p.pontuacao), 0) AS pontuacao
        FROM partidas p
        INNER JOIN usuarios u
            ON u.id = p.usuario_id
        WHERE u.tipo = 'aluno'
    ");

} else {

    $stmt = $pdo->prepare("
        SELECT
            COUNT(DISTINCT p.id) AS partidas,
            COALESCE(SUM(p.acertos), 0) AS acertos,
            COALESCE(SUM(p.erros), 0) AS erros,
            COALESCE(SUM(p.pontuacao), 0) AS pontuacao
        FROM partidas p
        INNER JOIN usuarios u
            ON u.id = p.usuario_id
        INNER JOIN professor_turmas pt
            ON pt.turma_id = u.turma_id
        WHERE pt.professor_id = ?
          AND u.tipo = 'aluno'
    ");

    $stmt->execute([$usuarioId]);
}

$dadosPartidas = $stmt->fetch();

$totalPartidas = (int) ($dadosPartidas['partidas'] ?? 0);
$totalAcertos = (int) ($dadosPartidas['acertos'] ?? 0);
$totalErros = (int) ($dadosPartidas['erros'] ?? 0);
$totalPontuacao = (int) ($dadosPartidas['pontuacao'] ?? 0);

$totalRespostas = $totalAcertos + $totalErros;

$taxaAcerto = $totalRespostas > 0
    ? round(($totalAcertos / $totalRespostas) * 100)
    : 0;

/*
|--------------------------------------------------------------------------
| Partidas recentes
|--------------------------------------------------------------------------
*/
if (ehAdmin()) {

    $stmt = $pdo->query("
        SELECT
            u.nome AS aluno,
            t.nome AS turma,
            j.nome AS jogo,
            p.acertos,
            p.erros,
            p.pontuacao,
            p.data_fim
        FROM partidas p
        INNER JOIN usuarios u
            ON u.id = p.usuario_id
        LEFT JOIN turmas t
            ON t.id = u.turma_id
        INNER JOIN jogos j
            ON j.id = p.jogo_id
        WHERE u.tipo = 'aluno'
        ORDER BY COALESCE(p.data_fim, p.data_inicio) DESC
        LIMIT 5
    ");

} else {

    $stmt = $pdo->prepare("
        SELECT
            u.nome AS aluno,
            t.nome AS turma,
            j.nome AS jogo,
            p.acertos,
            p.erros,
            p.pontuacao,
            p.data_fim
        FROM partidas p
        INNER JOIN usuarios u
            ON u.id = p.usuario_id
        INNER JOIN professor_turmas pt
            ON pt.turma_id = u.turma_id
            AND pt.professor_id = ?
        LEFT JOIN turmas t
            ON t.id = u.turma_id
        INNER JOIN jogos j
            ON j.id = p.jogo_id
        WHERE u.tipo = 'aluno'
        ORDER BY COALESCE(p.data_fim, p.data_inicio) DESC
        LIMIT 5
    ");

    $stmt->execute([$usuarioId]);
}

$partidasRecentes = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Inicial
|--------------------------------------------------------------------------
*/
$inicial = mb_strtoupper(
    mb_substr($professor['nome'], 0, 1)
);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard | MathPlay</title>

    <link
        rel="stylesheet"
        href="../assets/css/prof-dashboard.css"
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
                class="nav-item active"
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
                class="nav-item"
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


    <!-- CONTEÚDO PRINCIPAL -->

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


            <div class="topbar-title">

                <strong>
                    Dashboard
                </strong>

            </div>


            <div class="teacher-mini">

                <div class="teacher-avatar">

                    <?= htmlspecialchars($inicial) ?>

                </div>


                <div>

                    <strong>
                        <?= htmlspecialchars($professor['nome']) ?>
                    </strong>

                    <small>
                        Professor
                    </small>

                </div>

            </div>

        </header>


        <section class="content">

            <!-- CABEÇALHO -->

            <div class="page-header">

                <div>

                    <span class="eyebrow">
                        ÁREA DO PROFESSOR
                    </span>

                    <h1>
                        Olá, <?= htmlspecialchars($professor['nome']) ?>! 👋
                    </h1>

                    <p>
                        Acompanhe suas turmas e o desempenho
                        dos seus alunos.
                    </p>

                </div>

            </div>


            <!-- CARDS PRINCIPAIS -->

            <section class="stats-grid">

                <div class="stat-card">

                    <div class="stat-icon purple">
                        🏫
                    </div>

                    <div>

                        <span>
                            Minhas turmas
                        </span>

                        <strong>
                            <?= count($turmas) ?>
                        </strong>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon blue">
                        👨‍🎓
                    </div>

                    <div>

                        <span>
                            Alunos
                        </span>

                        <strong>
                            <?= $totalAlunos ?>
                        </strong>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon green">
                        🎮
                    </div>

                    <div>

                        <span>
                            Jogos ativos
                        </span>

                        <strong>
                            <?= $totalJogos ?>
                        </strong>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon yellow">
                        📈
                    </div>

                    <div>

                        <span>
                            Taxa de acerto
                        </span>

                        <strong>
                            <?= $taxaAcerto ?>%
                        </strong>

                    </div>

                </div>

            </section>


            <!-- VISÃO GERAL -->

            <section class="overview-grid">

                <div class="overview-card">

                    <div class="card-header">

                        <div>

                            <h2>
                                Visão geral
                            </h2>

                            <p>
                                Atividade dos seus alunos.
                            </p>

                        </div>

                    </div>


                    <div class="overview-stats">

                        <div>

                            <span>
                                Partidas
                            </span>

                            <strong>
                                <?= $totalPartidas ?>
                            </strong>

                        </div>


                        <div>

                            <span>
                                Acertos
                            </span>

                            <strong>
                                <?= $totalAcertos ?>
                            </strong>

                        </div>


                        <div>

                            <span>
                                Erros
                            </span>

                            <strong>
                                <?= $totalErros ?>
                            </strong>

                        </div>


                        <div>

                            <span>
                                Pontuação
                            </span>

                            <strong>
                                <?= $totalPontuacao ?>
                            </strong>

                        </div>

                    </div>


                    <div class="accuracy-area">

                        <div class="accuracy-header">

                            <span>
                                Aproveitamento
                            </span>

                            <strong>
                                <?= $taxaAcerto ?>%
                            </strong>

                        </div>


                        <div class="progress-bar">

                            <div
                                class="progress-fill"
                                style="width: <?= $taxaAcerto ?>%"
                            ></div>

                        </div>

                    </div>

                </div>


                <!-- ACESSOS RÁPIDOS -->

                <div class="quick-card">

                    <div class="card-header">

                        <div>

                            <h2>
                                Acessos rápidos
                            </h2>

                            <p>
                                Gerencie sua área.
                            </p>

                        </div>

                    </div>


                    <div class="quick-links">

                        <a
                            href="turmas.php"
                            class="quick-link"
                        >

                            <span>
                                🏫
                            </span>

                            <div>

                                <strong>
                                    Gerenciar turmas
                                </strong>

                                <small>
                                    Ver suas turmas
                                </small>

                            </div>

                            <b>
                                →
                            </b>

                        </a>


                        <a
                            href="alunos.php"
                            class="quick-link"
                        >

                            <span>
                                👨‍🎓
                            </span>

                            <div>

                                <strong>
                                    Ver alunos
                                </strong>

                                <small>
                                    Acompanhar estudantes
                                </small>

                            </div>

                            <b>
                                →
                            </b>

                        </a>


                        <a
                            href="desempenho.php"
                            class="quick-link"
                        >

                            <span>
                                📊
                            </span>

                            <div>

                                <strong>
                                    Desempenho
                                </strong>

                                <small>
                                    Analisar resultados
                                </small>

                            </div>

                            <b>
                                →
                            </b>

                        </a>


                        <a
                            href="relatorios.php"
                            class="quick-link"
                        >

                            <span>
                                📄
                            </span>

                            <div>

                                <strong>
                                    Relatórios
                                </strong>

                                <small>
                                    Consultar relatórios
                                </small>

                            </div>

                            <b>
                                →
                            </b>

                        </a>

                    </div>

                </div>

            </section>


            <!-- TURMAS -->

            <section class="section">

                <div class="section-header">

                    <div>

                        <h2>
                            Minhas turmas
                        </h2>

                        <p>
                            Acesso rápido às turmas cadastradas.
                        </p>

                    </div>


                    <a
                        href="turmas.php"
                        class="view-all"
                    >
                        Ver todas →
                    </a>

                </div>


                <?php if (!$turmas): ?>

                    <div class="empty-card">

                        <div>
                            🏫
                        </div>

                        <h3>
                            Nenhuma turma encontrada
                        </h3>

                        <p>
                            As turmas vinculadas ao professor
                            aparecerão aqui.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="classes-grid">

                        <?php foreach (array_slice($turmas, 0, 4) as $turma): ?>

                            <a
                                href="turmas.php?id=<?= (int) $turma['id'] ?>"
                                class="class-card"
                            >

                                <div class="class-card-top">

                                    <div class="class-icon">
                                        🏫
                                    </div>

                                    <span>
                                        <?= (int) $turma['ano_serie'] ?>º ano
                                    </span>

                                </div>


                                <h3>
                                    <?= htmlspecialchars($turma['nome']) ?>
                                </h3>


                                <p>
                                    <?= (int) $turma['total_alunos'] ?>
                                    aluno(s)
                                </p>


                                <div class="class-link">
                                    Ver turma →
                                </div>

                            </a>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>


            <!-- ATIVIDADE RECENTE -->

            <section class="section">

                <div class="section-header">

                    <div>

                        <h2>
                            Atividade recente
                        </h2>

                        <p>
                            Últimas partidas realizadas pelos alunos.
                        </p>

                    </div>

                </div>


                <?php if (!$partidasRecentes): ?>

                    <div class="empty-card">

                        <div>
                            📊
                        </div>

                        <h3>
                            Ainda não há atividades
                        </h3>

                        <p>
                            As partidas dos alunos aparecerão aqui
                            quando forem realizadas.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="activity-table-wrapper">

                        <table class="activity-table">

                            <thead>

                                <tr>

                                    <th>
                                        Aluno
                                    </th>

                                    <th>
                                        Turma
                                    </th>

                                    <th>
                                        Jogo
                                    </th>

                                    <th>
                                        Acertos
                                    </th>

                                    <th>
                                        Pontuação
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach ($partidasRecentes as $partida): ?>

                                    <tr>

                                        <td>

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $partida['aluno']
                                                ) ?>
                                            </strong>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $partida['turma']
                                                ?: '—'
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $partida['jogo']
                                            ) ?>

                                        </td>


                                        <td>

                                            <span class="accuracy-badge">

                                                <?= (int) $partida['acertos'] ?>

                                            </span>

                                        </td>


                                        <td>

                                            <strong>

                                                <?= (int) $partida['pontuacao'] ?>

                                            </strong>

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


<div
    class="sidebar-overlay"
    id="sidebar-overlay"
></div>


<script src="../assets/js/prof-dashboard.js"></script>

</body>

</html>