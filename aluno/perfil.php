<?php
require_once '../includes/auth.php';
require_once '../config/config.php';

protegerPagina('aluno');

$usuarioId = usuarioId();

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
    LIMIT 1
");

$stmt->execute([$usuarioId]);
$aluno = $stmt->fetch();

if (!$aluno) {
    header('Location: dashboard.php');
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

$stmt->execute([$usuarioId]);
$estatisticas = $stmt->fetch();

$partidas = (int) ($estatisticas['partidas'] ?? 0);
$acertos = (int) ($estatisticas['acertos'] ?? 0);
$erros = (int) ($estatisticas['erros'] ?? 0);
$pontuacao = (int) ($estatisticas['pontuacao'] ?? 0);

/*
|--------------------------------------------------------------------------
| Conquistas
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM usuario_conquistas
    WHERE usuario_id = ?
");

$stmt->execute([$usuarioId]);
$conquistas = (int) ($stmt->fetch()['total'] ?? 0);

/*
|--------------------------------------------------------------------------
| Taxa de acerto
|--------------------------------------------------------------------------
*/

$totalRespostas = $acertos + $erros;

$taxaAcerto = $totalRespostas > 0
    ? round(($acertos / $totalRespostas) * 100)
    : 0;

/*
|--------------------------------------------------------------------------
| Data de cadastro
|--------------------------------------------------------------------------
*/

$dataCadastro = '';

if (!empty($aluno['data_cadastro'])) {
    $dataCadastro = date(
        'd/m/Y',
        strtotime($aluno['data_cadastro'])
    );
}

/*
|--------------------------------------------------------------------------
| Inicial do nome
|--------------------------------------------------------------------------
*/

$inicial = mb_strtoupper(
    mb_substr($aluno['nome'], 0, 1)
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

    <title>Meu Perfil | MathPlay</title>

    <link
        rel="stylesheet"
        href="../assets/css/perfil.css"
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
                        Aprenda jogando
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
                <span>Início</span>
            </a>


            <a
                href="jogos.php"
                class="nav-item"
            >
                <span>🎮</span>
                <span>Jogos</span>
            </a>


            <a
                href="trilha.php"
                class="nav-item"
            >
                <span>🛤️</span>
                <span>Trilha</span>
            </a>


            <a
                href="conquistas.php"
                class="nav-item"
            >
                <span>🏆</span>
                <span>Conquistas</span>
            </a>


            <a
                href="progresso.php"
                class="nav-item"
            >
                <span>📊</span>
                <span>Progresso</span>
            </a>


            <a
                href="perfil.php"
                class="nav-item active"
            >
                <span>👤</span>
                <span>Perfil</span>
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


    <!-- CONTEÚDO -->

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
                    Meu Perfil
                </strong>

            </div>


            <div class="student-mini">

                <div class="student-avatar">

                    <?= htmlspecialchars($inicial) ?>

                </div>


                <div>

                    <strong>
                        <?= htmlspecialchars($aluno['nome']) ?>
                    </strong>

                    <small>
                        Nível <?= (int) $aluno['nivel'] ?>
                    </small>

                </div>

            </div>

        </header>


        <section class="content">

            <!-- CABEÇALHO -->

            <div class="page-header">

                <div>

                    <span class="eyebrow">
                        MINHA CONTA
                    </span>

                    <h1>
                        Meu Perfil 👤
                    </h1>

                    <p>
                        Veja suas informações e acompanhe
                        sua evolução no MathPlay.
                    </p>

                </div>

            </div>


            <!-- PERFIL PRINCIPAL -->

            <section class="profile-card">

                <div class="profile-avatar">

                    <?= htmlspecialchars($inicial) ?>

                </div>


                <div class="profile-main">

                    <h2>
                        <?= htmlspecialchars($aluno['nome']) ?>
                    </h2>

                    <p>
                        <?= htmlspecialchars($aluno['email']) ?>
                    </p>

                    <div class="profile-tags">

                        <span class="profile-tag">
                            🎓
                            <?= htmlspecialchars(
                                $aluno['turma']
                                ?: 'Turma não definida'
                            ) ?>
                        </span>

                        <span class="profile-tag">
                            ⭐
                            Nível <?= (int) $aluno['nivel'] ?>
                        </span>

                    </div>

                </div>


                <div class="xp-box">

                    <span>
                        XP atual
                    </span>

                    <strong>
                        <?= (int) $aluno['xp'] ?>
                    </strong>

                    <small>
                        pontos de experiência
                    </small>

                </div>

            </section>


            <!-- ESTATÍSTICAS -->

            <section class="section">

                <div class="section-title">

                    <div>

                        <h2>
                            Minhas estatísticas
                        </h2>

                        <p>
                            Seu desempenho nas partidas.
                        </p>

                    </div>

                </div>


                <div class="stats-grid">

                    <div class="stat-card">

                        <div class="stat-icon purple">
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

                    </div>


                    <div class="stat-card">

                        <div class="stat-icon green">
                            ✓
                        </div>

                        <div>

                            <span>
                                Acertos
                            </span>

                            <strong>
                                <?= $acertos ?>
                            </strong>

                        </div>

                    </div>


                    <div class="stat-card">

                        <div class="stat-icon red">
                            ✕
                        </div>

                        <div>

                            <span>
                                Erros
                            </span>

                            <strong>
                                <?= $erros ?>
                            </strong>

                        </div>

                    </div>


                    <div class="stat-card">

                        <div class="stat-icon yellow">
                            🏆
                        </div>

                        <div>

                            <span>
                                Pontuação
                            </span>

                            <strong>
                                <?= $pontuacao ?>
                            </strong>

                        </div>

                    </div>

                </div>

            </section>


            <!-- DESEMPENHO -->

            <section class="section">

                <div class="performance-card">

                    <div class="performance-header">

                        <div>

                            <h2>
                                Taxa de acerto
                            </h2>

                            <p>
                                Baseada nas respostas das suas partidas.
                            </p>

                        </div>


                        <strong class="performance-value">
                            <?= $taxaAcerto ?>%
                        </strong>

                    </div>


                    <div class="large-progress">

                        <div
                            class="large-progress-fill"
                            style="width: <?= $taxaAcerto ?>%"
                        ></div>

                    </div>


                    <div class="performance-footer">

                        <span>
                            <?= $acertos ?> acertos
                        </span>

                        <span>
                            <?= $erros ?> erros
                        </span>

                    </div>

                </div>

            </section>


            <!-- CONQUISTAS -->

            <section class="section">

                <div class="achievement-summary">

                    <div class="achievement-icon">
                        🏆
                    </div>

                    <div class="achievement-info">

                        <span>
                            Conquistas desbloqueadas
                        </span>

                        <strong>
                            <?= $conquistas ?>
                        </strong>

                        <small>
                            Continue jogando para conquistar
                            novas medalhas.
                        </small>

                    </div>


                    <a
                        href="conquistas.php"
                        class="achievement-button"
                    >
                        Ver conquistas
                        <span>→</span>
                    </a>

                </div>

            </section>


            <!-- INFORMAÇÕES DA CONTA -->

            <section class="section">

                <div class="account-card">

                    <div class="section-title">

                        <div>

                            <h2>
                                Informações da conta
                            </h2>

                        </div>

                    </div>


                    <div class="account-grid">

                        <div class="account-item">

                            <span>
                                Nome
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $aluno['nome']
                                ) ?>
                            </strong>

                        </div>


                        <div class="account-item">

                            <span>
                                E-mail
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $aluno['email']
                                ) ?>
                            </strong>

                        </div>


                        <div class="account-item">

                            <span>
                                Turma
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $aluno['turma']
                                    ?: 'Não definida'
                                ) ?>
                            </strong>

                        </div>


                        <div class="account-item">

                            <span>
                                Ano/Série
                            </span>

                            <strong>

                                <?php if (!empty($aluno['ano_serie'])): ?>

                                    <?= (int) $aluno['ano_serie'] ?>º ano

                                <?php else: ?>

                                    Não definido

                                <?php endif; ?>

                            </strong>

                        </div>


                        <div class="account-item">

                            <span>
                                Nível
                            </span>

                            <strong>
                                Nível <?= (int) $aluno['nivel'] ?>
                            </strong>

                        </div>


                        <div class="account-item">

                            <span>
                                Cadastro
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $dataCadastro ?: 'Não disponível'
                                ) ?>
                            </strong>

                        </div>

                    </div>

                </div>

            </section>


            <!-- AÇÕES -->

            <section class="profile-actions">

                <a
                    href="dashboard.php"
                    class="secondary-button"
                >
                    ← Voltar para início
                </a>


                <a
                    href="../logout.php"
                    class="logout-button"
                >
                    🚪 Sair da conta
                </a>

            </section>

        </section>

    </main>

</div>


<div
    class="sidebar-overlay"
    id="sidebar-overlay"
></div>


<script src="../assets/js/perfil.js"></script>

</body>

</html>