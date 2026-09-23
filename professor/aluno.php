<?php
require_once '../includes/auth.php';
require_once '../config/config.php';

protegerPagina(['professor', 'admin']);

$alunoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$alunoId) {
    header('Location: alunos.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Busca os dados do aluno
|--------------------------------------------------------------------------
*/

if (ehAdmin()) {

    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.nome,
            u.email,
            u.nivel,
            u.xp,
            u.data_cadastro,
            t.nome AS turma,
            t.ano_serie
        FROM usuarios u
        LEFT JOIN turmas t ON t.id = u.turma_id
        WHERE u.id = ?
          AND u.tipo = 'aluno'
        LIMIT 1
    ");

    $stmt->execute([$alunoId]);

} else {

    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.nome,
            u.email,
            u.nivel,
            u.xp,
            u.data_cadastro,
            t.nome AS turma,
            t.ano_serie
        FROM usuarios u
        LEFT JOIN turmas t
            ON t.id = u.turma_id
        WHERE u.id = ?
          AND u.tipo = 'aluno'
        LIMIT 1
    ");

    $stmt->execute([$alunoId]);
}

$aluno = $stmt->fetch();

if (!$aluno) {
    header('Location: alunos.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Estatísticas
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS partidas,
        COALESCE(SUM(acertos), 0) AS acertos,
        COALESCE(SUM(erros), 0) AS erros,
        COALESCE(SUM(pontuacao), 0) AS pontuacao
    FROM partidas
    WHERE usuario_id = ?
");

$stmt->execute([$alunoId]);

$estatisticas = $stmt->fetch();

$partidas = (int) ($estatisticas['partidas'] ?? 0);
$acertos = (int) ($estatisticas['acertos'] ?? 0);
$erros = (int) ($estatisticas['erros'] ?? 0);
$pontuacao = (int) ($estatisticas['pontuacao'] ?? 0);

$totalRespostas = $acertos + $erros;

$precisao = $totalRespostas > 0
    ? round(($acertos / $totalRespostas) * 100)
    : 0;

/*
|--------------------------------------------------------------------------
| Conquistas
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        c.id,
        c.nome,
        c.descricao,
        c.tipo,
        uc.data_desbloqueio
    FROM conquistas c
    INNER JOIN usuario_conquistas uc
        ON uc.conquista_id = c.id
    WHERE uc.usuario_id = ?
    ORDER BY uc.data_desbloqueio DESC
");

$stmt->execute([$alunoId]);

$conquistas = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Desempenho por jogo
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        j.id,
        j.nome,
        COUNT(p.id) AS partidas,
        COALESCE(SUM(p.acertos), 0) AS acertos,
        COALESCE(SUM(p.erros), 0) AS erros,
        COALESCE(SUM(p.pontuacao), 0) AS pontuacao
    FROM jogos j
    LEFT JOIN partidas p
        ON p.jogo_id = j.id
        AND p.usuario_id = ?
    WHERE j.ativo = 1
    GROUP BY j.id, j.nome
    ORDER BY j.id
");

$stmt->execute([$alunoId]);

$desempenhoJogos = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Partidas recentes
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        p.id,
        j.nome AS jogo,
        p.data_inicio,
        p.data_fim,
        p.acertos,
        p.erros,
        p.pontuacao
    FROM partidas p
    INNER JOIN jogos j
        ON j.id = p.jogo_id
    WHERE p.usuario_id = ?
    ORDER BY p.data_inicio DESC
    LIMIT 10
");

$stmt->execute([$alunoId]);

$partidasRecentes = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Ícones das conquistas
|--------------------------------------------------------------------------
*/

function iconeConquista(string $nome): string
{
    $icones = [
        'Primeira Vitória' => '🏆',
        'Mestre da Matemática' => '🧠',
        'Caixa Rápido' => '⚡',
        'Memória de Elefante' => '🐘',
        'Aluno Dedicado' => '⭐'
    ];

    return $icones[$nome] ?? '🏅';
}

function formatarData(?string $data): string
{
    if (!$data) {
        return '-';
    }

    return date('d/m/Y H:i', strtotime($data));
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($aluno['nome']) ?> - MathPlay</title>

    <link rel="stylesheet" href="../assets/css/prof-aluno.css">
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

            <a href="alunos.php" class="nav-item active">
                <span class="nav-icon">🎓</span>
                <span>Alunos</span>
            </a>

            <a href="desempenho.php" class="nav-item">
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

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

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
                <strong>Detalhes do aluno</strong>
            </div>

        </header>

        <section class="content">

            <!-- VOLTAR -->

            <a href="alunos.php" class="back-link">
                ← Voltar para alunos
            </a>

            <!-- PERFIL -->

            <section class="student-profile">

                <div class="student-avatar">
                    <?= htmlspecialchars(mb_strtoupper(mb_substr($aluno['nome'], 0, 1))) ?>
                </div>

                <div class="student-main-info">

                    <h1>
                        <?= htmlspecialchars($aluno['nome']) ?>
                    </h1>

                    <p>
                        <?= htmlspecialchars($aluno['email']) ?>
                    </p>

                    <div class="student-tags">

                        <span class="tag">
                            🎓
                            <?= htmlspecialchars($aluno['turma'] ?? 'Sem turma') ?>
                        </span>

                        <span class="tag">
                            Nível <?= (int) $aluno['nivel'] ?>
                        </span>

                    </div>

                </div>

                <div class="student-xp">

                    <span>XP</span>

                    <strong>
                        <?= number_format((int) $aluno['xp'], 0, ',', '.') ?>
                    </strong>

                </div>

            </section>

            <!-- ESTATÍSTICAS -->

            <section class="stats-grid">

                <article class="stat-card">

                    <div class="stat-icon">🎮</div>

                    <div>
                        <span>Partidas</span>
                        <strong><?= $partidas ?></strong>
                    </div>

                </article>

                <article class="stat-card">

                    <div class="stat-icon">✅</div>

                    <div>
                        <span>Acertos</span>
                        <strong><?= $acertos ?></strong>
                    </div>

                </article>

                <article class="stat-card">

                    <div class="stat-icon">❌</div>

                    <div>
                        <span>Erros</span>
                        <strong><?= $erros ?></strong>
                    </div>

                </article>

                <article class="stat-card">

                    <div class="stat-icon">🎯</div>

                    <div>
                        <span>Precisão</span>
                        <strong><?= $precisao ?>%</strong>
                    </div>

                </article>

                <article class="stat-card">

                    <div class="stat-icon">⭐</div>

                    <div>
                        <span>Pontuação</span>
                        <strong><?= number_format($pontuacao, 0, ',', '.') ?></strong>
                    </div>

                </article>

            </section>

            <div class="details-grid">

                <!-- DESEMPENHO POR JOGO -->

                <section class="panel">

                    <div class="panel-header">

                        <div>
                            <h2>Desempenho por jogo</h2>
                            <p>Resultados do aluno em cada jogo.</p>
                        </div>

                    </div>

                    <div class="games-list">

                        <?php foreach ($desempenhoJogos as $jogo): ?>

                            <?php
                            $jogoAcertos = (int) $jogo['acertos'];
                            $jogoErros = (int) $jogo['erros'];
                            $jogoTotal = $jogoAcertos + $jogoErros;

                            $jogoPrecisao = $jogoTotal > 0
                                ? round(($jogoAcertos / $jogoTotal) * 100)
                                : 0;
                            ?>

                            <article class="game-performance">

                                <div class="game-info">

                                    <div class="game-icon">
                                        <?= $jogo['id'] == 1 ? '📦' : '🧩' ?>
                                    </div>

                                    <div>
                                        <h3>
                                            <?= htmlspecialchars($jogo['nome']) ?>
                                        </h3>

                                        <span>
                                            <?= (int) $jogo['partidas'] ?> partida(s)
                                        </span>
                                    </div>

                                </div>

                                <div class="game-numbers">

                                    <div>
                                        <span>Acertos</span>
                                        <strong><?= $jogoAcertos ?></strong>
                                    </div>

                                    <div>
                                        <span>Erros</span>
                                        <strong><?= $jogoErros ?></strong>
                                    </div>

                                    <div>
                                        <span>Precisão</span>
                                        <strong><?= $jogoPrecisao ?>%</strong>
                                    </div>

                                </div>

                                <div class="mini-progress">

                                    <div
                                        class="mini-progress-fill"
                                        style="width: <?= $jogoPrecisao ?>%;"
                                    ></div>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                </section>

                <!-- CONQUISTAS -->

                <section class="panel">

                    <div class="panel-header">

                        <div>
                            <h2>Conquistas</h2>
                            <p>Medalhas desbloqueadas.</p>
                        </div>

                        <span class="achievement-count">
                            <?= count($conquistas) ?>
                        </span>

                    </div>

                    <?php if (empty($conquistas)): ?>

                        <div class="empty-state">
                            <span>🏅</span>
                            <strong>Nenhuma conquista ainda</strong>
                            <p>O aluno ainda não desbloqueou medalhas.</p>
                        </div>

                    <?php else: ?>

                        <div class="achievements-list">

                            <?php foreach ($conquistas as $conquista): ?>

                                <article class="achievement-item">

                                    <div class="achievement-icon">
                                        <?= iconeConquista($conquista['nome']) ?>
                                    </div>

                                    <div>

                                        <strong>
                                            <?= htmlspecialchars($conquista['nome']) ?>
                                        </strong>

                                        <p>
                                            <?= htmlspecialchars($conquista['descricao']) ?>
                                        </p>

                                        <small>
                                            Desbloqueada em
                                            <?= formatarData($conquista['data_desbloqueio']) ?>
                                        </small>

                                    </div>

                                </article>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </section>

            </div>

            <!-- PARTIDAS RECENTES -->

            <section class="panel recent-panel">

                <div class="panel-header">

                    <div>
                        <h2>Partidas recentes</h2>
                        <p>Últimas atividades realizadas pelo aluno.</p>
                    </div>

                </div>

                <?php if (empty($partidasRecentes)): ?>

                    <div class="empty-state">
                        <span>🎮</span>
                        <strong>Nenhuma partida realizada</strong>
                        <p>Este aluno ainda não possui partidas registradas.</p>
                    </div>

                <?php else: ?>

                    <div class="table-wrapper">

                        <table>

                            <thead>
                                <tr>
                                    <th>Jogo</th>
                                    <th>Data</th>
                                    <th>Acertos</th>
                                    <th>Erros</th>
                                    <th>Pontuação</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($partidasRecentes as $partida): ?>

                                    <tr>

                                        <td>
                                            <strong>
                                                <?= htmlspecialchars($partida['jogo']) ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <?= formatarData($partida['data_inicio']) ?>
                                        </td>

                                        <td>
                                            <span class="correct-value">
                                                <?= (int) $partida['acertos'] ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="wrong-value">
                                                <?= (int) $partida['erros'] ?>
                                            </span>
                                        </td>

                                        <td>
                                            <strong>
                                                <?= number_format((int) $partida['pontuacao'], 0, ',', '.') ?>
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

<script src="../assets/js/prof-aluno.js"></script>

</body>
</html>