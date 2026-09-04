<?php

require_once '../includes/auth.php';
require_once '../config/config.php';

protegerPagina('aluno');

$usuarioId = usuarioId();

/*
|--------------------------------------------------------------------------
| Buscar os jogos disponíveis
|--------------------------------------------------------------------------
*/

$sql = "
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
    AND j.nome IN (
        'Caixa Matemático',
        'Memória Matemática'
    )
    GROUP BY
        j.id,
        j.nome,
        j.descricao,
        j.tema
    ORDER BY j.id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$usuarioId]);

$jogos = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Jogos | MathPlay</title>

    <link
        rel="stylesheet"
        href="../assets/css/jogos-aluno.css"
    >

</head>

<body>

<div class="app">

    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

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
                class="nav-item"
            >
                <span class="nav-icon">⌂</span>
                <span>Início</span>
            </a>

            <a
                href="jogos.php"
                class="nav-item active"
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
                            mb_substr(
                                nomeUsuario(),
                                0,
                                1
                            )
                        )
                    ) ?>

                </div>

                <div class="user-info">

                    <strong>
                        <?= htmlspecialchars(nomeUsuario()) ?>
                    </strong>

                    <span>
                        Aluno
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

    <!-- =====================================================
         CONTEÚDO
    ====================================================== -->

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
                Jogos
            </div>

            <a
                href="perfil.php"
                class="topbar-profile"
                aria-label="Meu perfil"
            >

                <div class="topbar-avatar">

                    <?= htmlspecialchars(
                        strtoupper(
                            mb_substr(
                                nomeUsuario(),
                                0,
                                1
                            )
                        )
                    ) ?>

                </div>

            </a>

        </header>

        <div class="content">

            <!-- =================================================
                 CABEÇALHO
            ================================================== -->

            <section class="page-header">

                <div>

                    <span class="page-kicker">
                        🎮 Hora de aprender
                    </span>

                    <h1>
                        Escolha seu jogo
                    </h1>

                    <p>
                        Aprenda matemática enquanto se diverte.
                        Escolha um desafio e comece a jogar!
                    </p>

                </div>

                <div class="header-symbol">
                    ?
                </div>

            </section>

            <!-- =================================================
                 JOGOS
            ================================================== -->

            <section class="games-section">

                <?php if (count($jogos) > 0): ?>

                    <div class="games-grid">

                        <?php foreach ($jogos as $jogo): ?>

                            <?php

                            $nome = $jogo['nome'];

                            $icone = '🎮';

                            $classe = 'game-default';

                            $link = '#';

                            if ($nome === 'Caixa Matemático') {

                                $icone = '🛒';
                                $classe = 'game-caixa';

                                $link =
                                    '../jogos/caixa-matematico/index.php';

                            }

                            if ($nome === 'Memória Matemática') {

                                $icone = '🧠';
                                $classe = 'game-memoria';

                                $link =
                                    '../jogos/memoria-matematica/index.php';

                            }

                            $porcentagem =
                                (float) $jogo['porcentagem'];

                            ?>

                            <article class="game-card <?= $classe ?>">

                                <div class="game-card-top">

                                    <div class="game-icon">
                                        <?= $icone ?>
                                    </div>

                                    <span class="game-badge">
                                        Jogo
                                    </span>

                                </div>

                                <div class="game-info">

                                    <span class="game-theme">
                                        <?= htmlspecialchars(
                                            $jogo['tema']
                                        ) ?>
                                    </span>

                                    <h2>
                                        <?= htmlspecialchars(
                                            $jogo['nome']
                                        ) ?>
                                    </h2>

                                    <p>
                                        <?= htmlspecialchars(
                                            $jogo['descricao']
                                        ) ?>
                                    </p>

                                </div>

                                <div class="game-progress">

                                    <div class="progress-info">

                                        <span>
                                            Seu progresso
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

                                <a
                                    href="<?= htmlspecialchars($link) ?>"
                                    class="play-button"
                                >
                                    Jogar agora
                                    <span>→</span>
                                </a>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="empty-state">

                        <div class="empty-icon">
                            🎮
                        </div>

                        <h2>
                            Nenhum jogo disponível
                        </h2>

                        <p>
                            Os jogos estarão disponíveis em breve.
                        </p>

                    </div>

                <?php endif; ?>

            </section>

            <!-- =================================================
                 DICA
            ================================================== -->

            <section class="tip-card">

                <div class="tip-icon">
                    💡
                </div>

                <div>

                    <strong>
                        Dica MathPlay
                    </strong>

                    <p>
                        Não tenha medo de errar! Cada erro é uma
                        oportunidade para aprender e melhorar.
                    </p>

                </div>

            </section>

        </div>

    </main>

</div>

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>

<script src="../assets/js/jogos-aluno.js"></script>

</body>

</html>