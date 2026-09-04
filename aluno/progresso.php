<?php

require_once '../includes/auth.php';
require_once '../config/config.php';

protegerPagina('aluno');

$usuarioId = usuarioId();

/* =========================
   DADOS DO USUÁRIO
========================= */

$stmt = $pdo->prepare("
    SELECT nome, email, nivel, xp, turma_id
    FROM usuarios
    WHERE id = ?
");
$stmt->execute([$usuarioId]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: ../logout.php');
    exit;
}

/* =========================
   TURMA
========================= */

$turmaNome = 'Não definida';

if (!empty($usuario['turma_id'])) {

    $stmt = $pdo->prepare("
        SELECT nome
        FROM turmas
        WHERE id = ?
    ");

    $stmt->execute([$usuario['turma_id']]);
    $turma = $stmt->fetch();

    if ($turma) {
        $turmaNome = $turma['nome'];
    }
}

/* =========================
   ESTATÍSTICAS
========================= */

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

$partidas = (int) $estatisticas['partidas'];
$acertos = (int) $estatisticas['acertos'];
$erros = (int) $estatisticas['erros'];
$pontuacao = (int) $estatisticas['pontuacao'];

$totalRespostas = $acertos + $erros;

$taxaAcerto = $totalRespostas > 0
    ? round(($acertos / $totalRespostas) * 100)
    : 0;

/* =========================
   PROGRESSO DOS JOGOS
========================= */

$stmt = $pdo->prepare("
    SELECT
        j.id,
        j.nome,
        j.descricao,
        COALESCE(
            MAX(CASE WHEN p.dificuldade = 'facil' THEN p.porcentagem ELSE 0 END),
            0
        ) AS facil,
        COALESCE(
            MAX(CASE WHEN p.dificuldade = 'medio' THEN p.porcentagem ELSE 0 END),
            0
        ) AS medio,
        COALESCE(
            MAX(CASE WHEN p.dificuldade = 'dificil' THEN p.porcentagem ELSE 0 END),
            0
        ) AS dificil
    FROM jogos j
    LEFT JOIN progresso p
        ON p.jogo_id = j.id
        AND p.usuario_id = ?
    WHERE j.nome IN ('Caixa Matemático', 'Memória Matemática')
    GROUP BY j.id, j.nome, j.descricao
    ORDER BY j.id
");

$stmt->execute([$usuarioId]);
$jogos = $stmt->fetchAll();

/* =========================
   HISTÓRICO
========================= */

$stmt = $pdo->prepare("
    SELECT
        p.id,
        j.nome AS jogo,
        p.acertos,
        p.erros,
        p.pontuacao,
        p.data_inicio,
        p.data_fim
    FROM partidas p
    INNER JOIN jogos j
        ON j.id = p.jogo_id
    WHERE p.usuario_id = ?
    ORDER BY p.data_inicio DESC
    LIMIT 10
");

$stmt->execute([$usuarioId]);
$historico = $stmt->fetchAll();

/* =========================
   CONQUISTAS
========================= */

$stmt = $pdo->prepare("
    SELECT
        c.nome,
        c.descricao,
        c.tipo,
        uc.data_desbloqueio
    FROM usuario_conquistas uc
    INNER JOIN conquistas c
        ON c.id = uc.conquista_id
    WHERE uc.usuario_id = ?
    ORDER BY uc.data_desbloqueio DESC
");

$stmt->execute([$usuarioId]);
$conquistas = $stmt->fetchAll();

/* =========================
   XP / NÍVEL
========================= */

$xp = (int) $usuario['xp'];
$nivel = (int) $usuario['nivel'];

$xpNoNivel = $xp % 100;
$xpRestante = 100 - $xpNoNivel;

$progressoXP = $xpNoNivel;

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Meu Progresso - MathPlay</title>

    <link rel="stylesheet" href="../assets/css/progresso-aluno.css">
</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="logo">
            <span>Math</span>Play
        </div>

        <nav class="menu">

            <a href="dashboard.php">
                🏠
                <span>Início</span>
            </a>

            <a href="jogos.php">
                🎮
                <span>Jogos</span>
            </a>

            <a href="trilha.php">
                🧭
                <span>Trilha</span>
            </a>

            <a href="conquistas.php">
                🏆
                <span>Conquistas</span>
            </a>

            <a href="progresso.php" class="ativo">
                📊
                <span>Meu progresso</span>
            </a>

            <a href="perfil.php">
                👤
                <span>Meu perfil</span>
            </a>

        </nav>

        <div class="sidebar-bottom">

            <a href="../logout.php" class="logout">
                🚪
                <span>Sair</span>
            </a>

        </div>

    </aside>


    <!-- CONTEÚDO -->

    <main class="conteudo">

        <header class="topbar">

            <div>
                <h1>Meu progresso</h1>
                <p>Acompanhe sua evolução no MathPlay.</p>
            </div>

            <div class="usuario-topo">
                <strong><?= htmlspecialchars($usuario['nome']) ?></strong>
                <span><?= htmlspecialchars($turmaNome) ?></span>
            </div>

        </header>


        <!-- XP -->

        <section class="card xp-card">

            <div class="xp-info">

                <div class="icone-nivel">
                    ⭐
                </div>

                <div>
                    <span class="label">Nível atual</span>
                    <h2>Nível <?= $nivel ?></h2>
                </div>

            </div>

            <div class="xp-total">
                <strong><?= $xp ?> XP</strong>
                <span><?= $xpRestante ?> XP para o próximo nível</span>
            </div>

            <div class="barra">
                <div
                    class="barra-preenchida"
                    style="width: <?= $progressoXP ?>%;"
                ></div>
            </div>

        </section>


        <!-- ESTATÍSTICAS -->

        <section class="estatisticas">

            <div class="stat-card">
                <span class="stat-icone">🎮</span>
                <div>
                    <strong><?= $partidas ?></strong>
                    <span>Partidas</span>
                </div>
            </div>

            <div class="stat-card">
                <span class="stat-icone">✅</span>
                <div>
                    <strong><?= $acertos ?></strong>
                    <span>Acertos</span>
                </div>
            </div>

            <div class="stat-card">
                <span class="stat-icone">❌</span>
                <div>
                    <strong><?= $erros ?></strong>
                    <span>Erros</span>
                </div>
            </div>

            <div class="stat-card">
                <span class="stat-icone">🏆</span>
                <div>
                    <strong><?= $pontuacao ?></strong>
                    <span>Pontuação</span>
                </div>
            </div>

            <div class="stat-card">
                <span class="stat-icone">📈</span>
                <div>
                    <strong><?= $taxaAcerto ?>%</strong>
                    <span>Taxa de acerto</span>
                </div>
            </div>

        </section>


        <!-- PROGRESSO DOS JOGOS -->

        <section class="secao">

            <div class="titulo-secao">
                <div>
                    <h2>Progresso dos jogos</h2>
                    <p>Veja como está sua evolução em cada jogo.</p>
                </div>
            </div>

            <div class="jogos-progresso">

                <?php foreach ($jogos as $jogo): ?>

                    <?php
                    $facil = (float) $jogo['facil'];
                    $medio = (float) $jogo['medio'];
                    $dificil = (float) $jogo['dificil'];

                    $mediaJogo = round(
                        ($facil + $medio + $dificil) / 3
                    );
                    ?>

                    <article class="jogo-card">

                        <div class="jogo-header">

                            <div class="jogo-icone">
                                <?= $jogo['nome'] === 'Caixa Matemático' ? '🛒' : '🧠' ?>
                            </div>

                            <div>
                                <h3>
                                    <?= htmlspecialchars($jogo['nome']) ?>
                                </h3>

                                <p>
                                    <?= htmlspecialchars($jogo['descricao']) ?>
                                </p>
                            </div>

                        </div>

                        <div class="progresso-geral">

                            <div class="linha-progresso">

                                <span>Progresso geral</span>

                                <strong><?= $mediaJogo ?>%</strong>

                            </div>

                            <div class="barra">
                                <div
                                    class="barra-preenchida"
                                    style="width: <?= $mediaJogo ?>%;"
                                ></div>
                            </div>

                        </div>


                        <div class="dificuldades">

                            <div>
                                <span>Fácil</span>

                                <div class="mini-barra">
                                    <div
                                        style="width: <?= $facil ?>%;"
                                    ></div>
                                </div>

                                <strong><?= round($facil) ?>%</strong>
                            </div>


                            <div>
                                <span>Médio</span>

                                <div class="mini-barra">
                                    <div
                                        style="width: <?= $medio ?>%;"
                                    ></div>
                                </div>

                                <strong><?= round($medio) ?>%</strong>
                            </div>


                            <div>
                                <span>Difícil</span>

                                <div class="mini-barra">
                                    <div
                                        style="width: <?= $dificil ?>%;"
                                    ></div>
                                </div>

                                <strong><?= round($dificil) ?>%</strong>
                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>


        <!-- HISTÓRICO -->

        <section class="secao">

            <div class="titulo-secao">

                <div>
                    <h2>Histórico de partidas</h2>
                    <p>Suas últimas partidas realizadas.</p>
                </div>

            </div>

            <?php if (empty($historico)): ?>

                <div class="vazio">
                    <span>🎮</span>
                    <h3>Nenhuma partida ainda</h3>
                    <p>Jogue um dos desafios para começar seu histórico.</p>

                    <a href="jogos.php" class="botao">
                        Ver jogos
                    </a>
                </div>

            <?php else: ?>

                <div class="tabela-container">

                    <table>

                        <thead>

                            <tr>
                                <th>Jogo</th>
                                <th>Acertos</th>
                                <th>Erros</th>
                                <th>Pontuação</th>
                                <th>Data</th>
                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($historico as $partida): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($partida['jogo']) ?>
                                </td>

                                <td class="acerto">
                                    <?= (int) $partida['acertos'] ?>
                                </td>

                                <td class="erro">
                                    <?= (int) $partida['erros'] ?>
                                </td>

                                <td>
                                    <?= (int) $partida['pontuacao'] ?> pts
                                </td>

                                <td>
                                    <?= date(
                                        'd/m/Y H:i',
                                        strtotime($partida['data_inicio'])
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>


        <!-- CONQUISTAS -->

        <section class="secao">

            <div class="titulo-secao">

                <div>
                    <h2>Minhas conquistas</h2>
                    <p>Medalhas que você já desbloqueou.</p>
                </div>

            </div>

            <?php if (empty($conquistas)): ?>

                <div class="vazio">
                    <span>🏆</span>
                    <h3>Nenhuma conquista ainda</h3>
                    <p>Continue jogando para desbloquear medalhas.</p>
                </div>

            <?php else: ?>

                <div class="conquistas">

                    <?php foreach ($conquistas as $conquista): ?>

                        <div class="conquista">

                            <div class="conquista-icone">
                                🏅
                            </div>

                            <div>

                                <h3>
                                    <?= htmlspecialchars($conquista['nome']) ?>
                                </h3>

                                <p>
                                    <?= htmlspecialchars($conquista['descricao']) ?>
                                </p>

                                <small>
                                    Desbloqueada em
                                    <?= date(
                                        'd/m/Y',
                                        strtotime($conquista['data_desbloqueio'])
                                    ) ?>
                                </small>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>
</html>