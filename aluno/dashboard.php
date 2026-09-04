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
    SELECT
        u.id,
        u.nome,
        u.email,
        u.nivel,
        u.xp,
        t.nome AS turma
    FROM usuarios u
    LEFT JOIN turmas t ON t.id = u.turma_id
    WHERE u.id = ?
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

/*
|--------------------------------------------------------------------------
| Estatísticas
|--------------------------------------------------------------------------
*/

$sqlEstatisticas = "
    SELECT
        COUNT(*) AS partidas,
        COALESCE(SUM(acertos), 0) AS acertos,
        COALESCE(SUM(erros), 0) AS erros,
        COALESCE(SUM(pontuacao), 0) AS pontos
    FROM partidas
    WHERE usuario_id = ?
";

$stmtEstatisticas = $pdo->prepare($sqlEstatisticas);
$stmtEstatisticas->execute([$usuarioId]);

$estatisticas = $stmtEstatisticas->fetch();

$partidas = (int) ($estatisticas['partidas'] ?? 0);
$acertos = (int) ($estatisticas['acertos'] ?? 0);
$erros = (int) ($estatisticas['erros'] ?? 0);
$pontos = (int) ($estatisticas['pontos'] ?? 0);

$totalRespostas = $acertos + $erros;

$taxaAcerto = $totalRespostas > 0
    ? round(($acertos / $totalRespostas) * 100)
    : 0;

/*
|--------------------------------------------------------------------------
| Conquistas
|--------------------------------------------------------------------------
*/

$sqlConquistas = "
    SELECT
        c.id,
        c.nome,
        c.descricao,
        c.tipo,
        uc.data_desbloqueio
    FROM usuario_conquistas uc
    INNER JOIN conquistas c
        ON c.id = uc.conquista_id
    WHERE uc.usuario_id = ?
    ORDER BY uc.data_desbloqueio DESC
    LIMIT 3
";

$stmtConquistas = $pdo->prepare($sqlConquistas);
$stmtConquistas->execute([$usuarioId]);

$conquistas = $stmtConquistas->fetchAll();

/*
|--------------------------------------------------------------------------
| Jogos
|--------------------------------------------------------------------------
*/

$sqlJogos = "
    SELECT
        j.id,
        j.nome,
        j.descricao,
        j.tema,
        COALESCE(
            MAX(
                CASE
                    WHEN p.usuario_id = ?
                    THEN p.porcentagem
                    ELSE 0
                END
            ),
            0
        ) AS porcentagem
    FROM jogos j
    LEFT JOIN progresso p
        ON p.jogo_id = j.id
    WHERE j.ativo = 1
    GROUP BY j.id, j.nome, j.descricao, j.tema
    ORDER BY j.id
    LIMIT 4
";

$stmtJogos = $pdo->prepare($sqlJogos);
$stmtJogos->execute([$usuarioId]);

$jogos = $stmtJogos->fetchAll();

/*
|--------------------------------------------------------------------------
| XP e nível
|--------------------------------------------------------------------------
*/

$xp = (int) $aluno['xp'];
$nivel = (int) $aluno['nivel'];

/*
| Exemplo:
| Nível 1 = 0 a 99 XP
| Nível 2 = 100 a 199 XP
| etc.
*/

$xpBaseNivel = ($nivel - 1) * 100;
$xpProximoNivel = $nivel * 100;

$xpNoNivel = $xp - $xpBaseNivel;

if ($xpNoNivel < 0) {
    $xpNoNivel = 0;
}

$xpNecessario = 100;

$porcentagemXp = min(
    100,
    max(
        0,
        round(($xpNoNivel / $xpNecessario) * 100)
    )
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
        href="../assets/css/dashboard-aluno.css"
    >

</head>

<body>

<div class="app">

    <!-- =========================================================
         SIDEBAR
    ========================================================== -->

    <aside class="sidebar" id="sidebar">

        <div class="sidebar-header">

            <a href="dashboard.php" class="logo">

                <span class="logo-icon">
                    M
                </span>

                <span class="logo-text">
                    Math<span>Play</span>
                </span>

            </a>

            <button
                type="button"
                class="sidebar-close"
                id="sidebarClose"
                aria-label="Fechar menu"
            >
                ×
            </button>

        </div>

        <nav class="sidebar-nav">

            <a
                href="dashboard.php"
                class="nav-item active"
            >
                <span class="nav-icon">⌂</span>
                <span>Início</span>
            </a>

            <a
                href="jogos.php"
                class="nav-item"
            >
                <span class="nav-icon">🎮</span>
                <span>Jogos</span>
            </a>

            <a
                href="trilha.php"
                class="nav-item"
            >
                <span class="nav-icon">📚</span>
                <span>Trilha</span>
            </a>

            <a
                href="conquistas.php"
                class="nav-item"
            >
                <span class="nav-icon">🏆</span>
                <span>Conquistas</span>
            </a>

            <a
                href="progresso.php"
                class="nav-item"
            >
                <span class="nav-icon">📊</span>
                <span>Meu progresso</span>
            </a>

            <a
                href="perfil.php"
                class="nav-item"
            >
                <span class="nav-icon">👤</span>
                <span>Meu perfil</span>
            </a>

        </nav>

        <div class="sidebar-bottom">

            <div class="sidebar-user">

                <div class="user-avatar">

                    <?= htmlspecialchars(
                        strtoupper(
                            mb_substr($aluno['nome'], 0, 1)
                        )
                    ) ?>

                </div>

                <div class="user-info">

                    <strong>
                        <?= htmlspecialchars($aluno['nome']) ?>
                    </strong>

                    <span>
                        Nível <?= $nivel ?>
                    </span>

                </div>

            </div>

            <div class="logout-divider"></div>

            <a
                href="../logout.php"
                class="logout-link"
            >
                <span>↪</span>
                Sair
            </a>

        </div>

    </aside>

    <!-- =========================================================
         CONTEÚDO
    ========================================================== -->

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

                <span>
                    Área do aluno
                </span>

            </div>

            <a
                href="perfil.php"
                class="topbar-profile"
                aria-label="Meu perfil"
            >

                <div class="topbar-avatar">

                    <?= htmlspecialchars(
                        strtoupper(
                            mb_substr($aluno['nome'], 0, 1)
                        )
                    ) ?>

                </div>

            </a>

        </header>

        <div class="content">

            <!-- =================================================
                 BOAS-VINDAS
            ================================================== -->

            <section class="welcome-section">

                <div>

                    <span class="welcome-label">
                        👋 Olá!
                    </span>

                    <h1>
                        <?= htmlspecialchars($aluno['nome']) ?>
                    </h1>

                    <p>
                        Pronto para continuar sua jornada
                        matemática?
                    </p>

                    <?php if (!empty($aluno['turma'])): ?>

                        <span class="class-badge">
                            Turma <?= htmlspecialchars($aluno['turma']) ?>
                        </span>

                    <?php endif; ?>

                </div>

                <div class="welcome-decoration">
                    ∑
                </div>

            </section>

            <!-- =================================================
                 XP / NÍVEL
            ================================================== -->

            <section class="level-card">

                <div class="level-icon">
                    ⭐
                </div>

                <div class="level-content">

                    <div class="level-top">

                        <div>

                            <span class="small-label">
                                Seu nível
                            </span>

                            <strong>
                                Nível <?= $nivel ?>
                            </strong>

                        </div>

                        <span class="xp-value">
                            <?= $xp ?> XP
                        </span>

                    </div>

                    <div class="xp-bar">

                        <div
                            class="xp-progress"
                            style="width: <?= $porcentagemXp ?>%;"
                        ></div>

                    </div>

                    <div class="xp-footer">

                        <span>
                            <?= $xpNoNivel ?> / <?= $xpNecessario ?> XP
                        </span>

                        <span>
                            Próximo nível: <?= $xpProximoNivel ?> XP
                        </span>

                    </div>

                </div>

            </section>

            <!-- =================================================
                 ESTATÍSTICAS
            ================================================== -->

            <section class="stats-grid">

                <article class="stat-card">

                    <div class="stat-icon">
                        🎮
                    </div>

                    <div>

                        <span>
                            Partidas
                        </span>

                        <strong>
                            <?= $partidas ?>
                        </strong>

                    </div>

                </article>

                <article class="stat-card">

                    <div class="stat-icon">
                        ✅
                    </div>

                    <div>

                        <span>
                            Acertos
                        </span>

                        <strong>
                            <?= $acertos ?>
                        </strong>

                    </div>

                </article>

                <article class="stat-card">

                    <div class="stat-icon">
                        ⭐
                    </div>

                    <div>

                        <span>
                            Pontos
                        </span>

                        <strong>
                            <?= $pontos ?>
                        </strong>

                    </div>

                </article>

                <article class="stat-card">

                    <div class="stat-icon">
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

                </article>

            </section>

            <!-- =================================================
                 JOGOS
            ================================================== -->

            <section class="section">

                <div class="section-heading">

                    <div>

                        <span class="section-kicker">
                            Continue aprendendo
                        </span>

                        <h2>
                            Seus jogos
                        </h2>

                    </div>

                    <a
                        href="jogos.php"
                        class="see-all"
                    >
                        Ver todos →
                    </a>

                </div>

                <?php if (count($jogos) > 0): ?>

                    <div class="games-grid">

                        <?php foreach ($jogos as $jogo): ?>

                            <?php

                            $porcentagem =
                                (float) $jogo['porcentagem'];

                            $nomeJogo = $jogo['nome'];

                            $icone = '🎮';

                            if (
                                stripos(
                                    $nomeJogo,
                                    'Caixa'
                                ) !== false
                            ) {
                                $icone = '🛒';
                            }

                            if (
                                stripos(
                                    $nomeJogo,
                                    'Memória'
                                ) !== false
                            ) {
                                $icone = '🧠';
                            }

                            if (
                                stripos(
                                    $nomeJogo,
                                    'Espacial'
                                ) !== false
                            ) {
                                $icone = '🚀';
                            }

                            if (
                                stripos(
                                    $nomeJogo,
                                    'Construtor'
                                ) !== false
                            ) {
                                $icone = '📐';
                            }

                            ?>

                            <article class="game-card">

                                <div class="game-icon">
                                    <?= $icone ?>
                                </div>

                                <div class="game-info">

                                    <span class="game-theme">
                                        <?= htmlspecialchars(
                                            $jogo['tema']
                                        ) ?>
                                    </span>

                                    <h3>
                                        <?= htmlspecialchars(
                                            $jogo['nome']
                                        ) ?>
                                    </h3>

                                    <p>
                                        <?= htmlspecialchars(
                                            $jogo['descricao']
                                        ) ?>
                                    </p>

                                </div>

                                <div class="game-progress">

                                    <div class="progress-header">

                                        <span>
                                            Progresso
                                        </span>

                                        <strong>
                                            <?= round($porcentagem) ?>%
                                        </strong>

                                    </div>

                                    <div class="progress-bar">

                                        <div
                                            class="progress-fill"
                                            style="width: <?= $porcentagem ?>%;"
                                        ></div>

                                    </div>

                                </div>

                                <!-- =================================================
                                     BOTÃO DO JOGO
                                ================================================== -->

                                <?php if ($nomeJogo === 'Caixa Matemático'): ?>

                                    <a
                                        href="../jogos/caixa-matematico/index.php"
                                        class="game-button"
                                    >
                                        Jogar
                                    </a>

                                <?php elseif ($nomeJogo === 'Memória Matemática'): ?>

                                    <a
                                        href="../jogos/memoria-matematica/index.php"
                                        class="game-button"
                                    >
                                        Jogar
                                    </a>

                                <?php else: ?>

                                    <a
                                        href="jogos.php"
                                        class="game-button"
                                    >
                                        Ver jogo
                                    </a>

                                <?php endif; ?>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="empty-state">

                        <div>
                            🎮
                        </div>

                        <h3>
                            Nenhum jogo disponível
                        </h3>

                        <p>
                            Os jogos aparecerão aqui quando
                            estiverem disponíveis.
                        </p>

                    </div>

                <?php endif; ?>

            </section>

            <!-- =================================================
                 CONQUISTAS
            ================================================== -->

            <section class="section">

                <div class="section-heading">

                    <div>

                        <span class="section-kicker">
                            Suas recompensas
                        </span>

                        <h2>
                            Conquistas recentes
                        </h2>

                    </div>

                    <a
                        href="conquistas.php"
                        class="see-all"
                    >
                        Ver todas →
                    </a>

                </div>

                <?php if (count($conquistas) > 0): ?>

                    <div class="achievements-grid">

                        <?php foreach ($conquistas as $conquista): ?>

                            <article class="achievement-card">

                                <div class="achievement-icon">
                                    🏆
                                </div>

                                <div>

                                    <h3>
                                        <?= htmlspecialchars(
                                            $conquista['nome']
                                        ) ?>
                                    </h3>

                                    <p>
                                        <?= htmlspecialchars(
                                            $conquista['descricao']
                                        ) ?>
                                    </p>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="empty-state">

                        <div>
                            🏆
                        </div>

                        <h3>
                            Ainda não há conquistas
                        </h3>

                        <p>
                            Continue jogando para desbloquear
                            suas primeiras medalhas!
                        </p>

                    </div>

                <?php endif; ?>

            </section>

        </div>

    </main>

</div>

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>

<script src="../assets/js/dashboard-aluno.js"></script>

</body>

</html>

