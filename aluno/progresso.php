
<?php

require_once '../includes/auth.php';
require_once '../config/config.php';

protegerPagina('aluno');

$usuarioId = usuarioId();

/*
|--------------------------------------------------------------------------
| Dados do aluno
|--------------------------------------------------------------------------
*/

$sqlUsuario = "
    SELECT id, nome, nivel, xp
    FROM usuarios
    WHERE id = ?
    LIMIT 1
";

$stmtUsuario = $pdo->prepare($sqlUsuario);
$stmtUsuario->execute([$usuarioId]);
$aluno = $stmtUsuario->fetch();

if (!$aluno) {
    session_unset();
    session_destroy();
    header('Location: ../login.php');
    exit;
}

$nomeAluno = $aluno['nome'];
$nivel = (int) $aluno['nivel'];
$xp = (int) $aluno['xp'];

$inicial = mb_strtoupper(
    mb_substr($nomeAluno, 0, 1, 'UTF-8'),
    'UTF-8'
);

/*
|--------------------------------------------------------------------------
| Estatísticas dos jogos
|--------------------------------------------------------------------------
*/

$sqlJogos = "
    SELECT
        j.id,
        j.nome,
        j.descricao,
        j.tema,
        COALESCE(MAX(p.porcentagem), 0) AS porcentagem
    FROM jogos j
    LEFT JOIN progresso p
        ON p.jogo_id = j.id
        AND p.usuario_id = ?
    WHERE j.ativo = 1
    GROUP BY j.id, j.nome, j.descricao, j.tema
    ORDER BY j.id ASC
";

$stmtJogos = $pdo->prepare($sqlJogos);
$stmtJogos->execute([$usuarioId]);
$jogos = $stmtJogos->fetchAll();

$totalJogos = count($jogos);
$jogosIniciados = 0;
$jogosConcluidos = 0;
$somaProgresso = 0;

foreach ($jogos as $jogo) {
    $porcentagem = min(100, max(0, (float) $jogo['porcentagem']));

    $somaProgresso += $porcentagem;

    if ($porcentagem > 0) {
        $jogosIniciados++;
    }

    if ($porcentagem >= 100) {
        $jogosConcluidos++;
    }
}

$progressoGeral = $totalJogos > 0
    ? round($somaProgresso / $totalJogos)
    : 0;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Meu progresso | MathPower</title>

    <link rel="stylesheet" href="../assets/css/jogos-aluno.css">
    <link rel="stylesheet" href="../assets/css/progresso-aluno.css">
    <link rel="stylesheet" href="../assets/css/acessibilidade.css">

</head>

<body>

<div class="app">

    <aside class="sidebar" id="sidebar">

        <div class="sidebar-header">
            <a href="dashboard.php" class="logo">
                <span class="logo-icon">M</span>
                <span class="logo-text">Math<span>Power</span></span>
            </a>

            <button
                type="button"
                class="sidebar-close"
                id="sidebarClose"
                aria-label="Fechar menu"
            >×</button>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">
                <span class="nav-icon">⌂</span>
                <span>Início</span>
            </a>

            <a href="jogos.php" class="nav-item">
                <span class="nav-icon">🎮</span>
                <span>Jogos</span>
            </a>

            <a href="trilha.php" class="nav-item">
                <span class="nav-icon">📚</span>
                <span>Trilha</span>
            </a>

            <a href="conquistas.php" class="nav-item">
                <span class="nav-icon">🏆</span>
                <span>Conquistas</span>
            </a>

            <a href="progresso.php" class="nav-item active">
                <span class="nav-icon">📊</span>
                <span>Progresso</span>
            </a>

            <a href="perfil.php" class="nav-item">
                <span class="nav-icon">👤</span>
                <span>Perfil</span>
            </a>
        </nav>

        <div class="sidebar-bottom">

            <div class="sidebar-user">
                <div class="user-avatar">
                    <?= htmlspecialchars($inicial) ?>
                </div>

                <div class="user-info">
                    <strong>
                        <?= htmlspecialchars($nomeAluno) ?>
                    </strong>

                    <span>Nível <?= $nivel ?></span>
                </div>
            </div>

            <div class="logout-divider"></div>

            <a href="../logout.php" class="logout-link">
                <span>↪</span>
                <span>Sair</span>
            </a>
        </div>

    </aside>

    <main class="main-content">

        <header class="topbar">
            <button
                type="button"
                class="menu-button"
                id="menuButton"
                aria-label="Abrir menu"
            >☰</button>

            <div class="topbar-title">Meu progresso</div>
        </header>

        <div class="content">

            <section class="page-header">
                <div>
                    <span class="page-kicker">📊 Sua jornada</span>
                    <h1>Meu progresso</h1>
                    <p>
                        Acompanhe suas atividades, seu XP e sua evolução
                        no MathPower.
                    </p>
                </div>

                <div class="header-symbol">🚀</div>
            </section>

            <section class="stats-grid">

                <article class="stat-card">
                    <div class="stat-icon">⭐</div>
                    <span>Experiência total</span>
                    <strong><?= number_format($xp, 0, ',', '.') ?> XP</strong>
                    <p>Seus pontos acumulados</p>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">🏆</div>
                    <span>Nível atual</span>
                    <strong><?= $nivel ?></strong>
                    <p>Continue aprendendo!</p>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">🎮</div>
                    <span>Jogos iniciados</span>
                    <strong><?= $jogosIniciados ?></strong>
                    <p>Jogos com progresso registrado</p>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">✅</div>
                    <span>Jogos concluídos</span>
                    <strong><?= $jogosConcluidos ?></strong>
                    <p>Jogos com 100% de progresso</p>
                </article>

            </section>

            <section class="progress-overview card">
                <div class="section-heading">
                    <div>
                        <span class="page-kicker">📈 Visão geral</span>
                        <h2>Seu avanço nos jogos</h2>
                    </div>

                    <strong class="percentage">
                        <?= $progressoGeral ?>%
                    </strong>
                </div>

                <div class="progress-bar">
                    <div
                        class="progress-fill"
                        style="width: <?= $progressoGeral ?>%;"
                    ></div>
                </div>

                <p>
                    Você concluiu, em média,
                    <?= $progressoGeral ?>% do progresso dos jogos disponíveis.
                </p>
            </section>

            <section class="games-progress card">
                <div class="section-heading">
                    <div>
                        <span class="page-kicker">🎯 Atividades</span>
                        <h2>Progresso por jogo</h2>
                    </div>
                </div>

                <?php if ($totalJogos > 0): ?>

                    <div class="progress-list">

                        <?php foreach ($jogos as $jogo): ?>

                            <?php
                            $porcentagem = min(
                                100,
                                max(0, (float) $jogo['porcentagem'])
                            );
                            ?>

                            <article class="progress-item">

                                <div class="game-icon">🎮</div>

                                <div class="progress-item-info">
                                    <h3>
                                        <?= htmlspecialchars($jogo['nome']) ?>
                                    </h3>

                                    <p>
                                        <?= htmlspecialchars(
                                            $jogo['descricao'] ?? ''
                                        ) ?>
                                    </p>

                                    <div class="progress-bar">
                                        <div
                                            class="progress-fill"
                                            style="width: <?= $porcentagem ?>%;"
                                        ></div>
                                    </div>
                                </div>

                                <strong class="game-percentage">
                                    <?= round($porcentagem) ?>%
                                </strong>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="empty-state">
                        <div class="empty-icon">🎮</div>
                        <h3>Nenhum jogo disponível</h3>
                        <p>
                            Quando os jogos estiverem disponíveis,
                            seu progresso aparecerá aqui.
                        </p>
                        <a href="jogos.php" class="action-button">
                            Ver jogos
                        </a>
                    </div>

                <?php endif; ?>
            </section>

            <section class="tip-card">
                <div class="tip-icon">💡</div>

                <div>
                    <strong>Dica MathPower</strong>
                    <p>
                        Pratique no seu ritmo e acompanhe sua evolução.
                        Cada atividade é uma oportunidade de aprender algo novo!
                    </p>
                </div>
            </section>

        </div>
    </main>

</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script src="../assets/js/jogos-aluno.js"></script>
<?php require_once __DIR__ . '/../includes/acessibilidade.php'; ?>
<script src="../assets/js/acessibilidade.js" defer></script>

</body>
</html>
