<?php

require_once '../config/config.php';
require_once '../includes/auth.php';

protegerPagina(['professor', 'admin']);

/* =========================
   DADOS DO USUÁRIO
========================= */

$usuarioId = usuarioId();
$nome = nomeUsuario();
$tipo = tipoUsuario();

/* =========================
   ESTATÍSTICAS GERAIS
========================= */

$stmt = $pdo->query("
    SELECT COUNT(*) AS total
    FROM usuarios
    WHERE tipo = 'aluno'
");
$totalAlunos = (int) $stmt->fetch()['total'];

if ($tipo === 'admin') {

    $stmt = $pdo->query("
        SELECT COUNT(*) AS total
        FROM turmas
        WHERE ativo = 1
    ");

} else {

    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM professor_turmas pt
        INNER JOIN turmas t ON t.id = pt.turma_id
        WHERE pt.professor_id = ?
        AND t.ativo = 1
    ");

    $stmt->execute([$usuarioId]);
}

$totalTurmas = (int) $stmt->fetch()['total'];

/* =========================
   PARTIDAS
========================= */

if ($tipo === 'admin') {

    $stmt = $pdo->query("
        SELECT COUNT(*) AS total
        FROM partidas
    ");

} else {

    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM partidas p
        INNER JOIN usuarios u ON u.id = p.usuario_id
        INNER JOIN professor_turmas pt ON pt.turma_id = u.turma_id
        WHERE pt.professor_id = ?
    ");

    $stmt->execute([$usuarioId]);
}

$totalPartidas = (int) $stmt->fetch()['total'];

/* =========================
   MÉDIA DE ACERTOS
========================= */

if ($tipo === 'admin') {

    $stmt = $pdo->query("
        SELECT
            COALESCE(
                ROUND(
                    (SUM(acertos) /
                    NULLIF(SUM(acertos + erros), 0)) * 100,
                    1
                ),
                0
            ) AS media
        FROM partidas
    ");

} else {

    $stmt = $pdo->prepare("
        SELECT
            COALESCE(
                ROUND(
                    (SUM(p.acertos) /
                    NULLIF(SUM(p.acertos + p.erros), 0)) * 100,
                    1
                ),
                0
            ) AS media
        FROM partidas p
        INNER JOIN usuarios u ON u.id = p.usuario_id
        INNER JOIN professor_turmas pt ON pt.turma_id = u.turma_id
        WHERE pt.professor_id = ?
    ");

    $stmt->execute([$usuarioId]);
}

$mediaAcertos = (float) $stmt->fetch()['media'];

/* =========================
   TURMAS
========================= */

if ($tipo === 'admin') {

    $stmt = $pdo->query("
        SELECT
            t.id,
            t.nome,
            t.ano_serie,
            COUNT(u.id) AS total_alunos
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
            COUNT(u.id) AS total_alunos
        FROM professor_turmas pt
        INNER JOIN turmas t
            ON t.id = pt.turma_id
        LEFT JOIN usuarios u
            ON u.turma_id = t.id
            AND u.tipo = 'aluno'
        WHERE pt.professor_id = ?
        AND t.ativo = 1
        GROUP BY t.id, t.nome, t.ano_serie
        ORDER BY t.ano_serie, t.nome
    ");

    $stmt->execute([$usuarioId]);
}

$turmas = $stmt->fetchAll();

/* =========================
   ÚLTIMAS PARTIDAS
========================= */

if ($tipo === 'admin') {

    $stmt = $pdo->query("
        SELECT
            p.id,
            u.nome AS aluno,
            j.nome AS jogo,
            p.acertos,
            p.erros,
            p.pontuacao,
            p.data_inicio
        FROM partidas p
        INNER JOIN usuarios u ON u.id = p.usuario_id
        INNER JOIN jogos j ON j.id = p.jogo_id
        ORDER BY p.data_inicio DESC
        LIMIT 8
    ");

} else {

    $stmt = $pdo->prepare("
        SELECT
            p.id,
            u.nome AS aluno,
            j.nome AS jogo,
            p.acertos,
            p.erros,
            p.pontuacao,
            p.data_inicio
        FROM partidas p
        INNER JOIN usuarios u ON u.id = p.usuario_id
        INNER JOIN jogos j ON j.id = p.jogo_id
        INNER JOIN professor_turmas pt
            ON pt.turma_id = u.turma_id
        WHERE pt.professor_id = ?
        ORDER BY p.data_inicio DESC
        LIMIT 8
    ");

    $stmt->execute([$usuarioId]);
}

$ultimasPartidas = $stmt->fetchAll();

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
        href="../assets/css/global.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/professor-dashboard.css"
    >

</head>

<body>

<div class="layout">

    <?php require_once '../includes/sidebar-professor.php'; ?>

    <main class="conteudo-principal">

        <header class="cabecalho-pagina">

            <div>

                <p class="saudacao">
                    Olá, <?= htmlspecialchars($nome) ?>! 👋
                </p>

                <h1>
                    Dashboard
                </h1>

                <p>
                    Acompanhe o desempenho dos alunos e das turmas.
                </p>

            </div>

        </header>


        <!-- =========================
             CARDS DE ESTATÍSTICAS
        ========================== -->

        <section class="cards-estatisticas">

            <article class="card-estatistica">

                <span class="icone">
                    👥
                </span>

                <div>

                    <span class="titulo">
                        Alunos
                    </span>

                    <strong>
                        <?= $totalAlunos ?>
                    </strong>

                </div>

            </article>


            <article class="card-estatistica">

                <span class="icone">
                    🏫
                </span>

                <div>

                    <span class="titulo">
                        Turmas
                    </span>

                    <strong>
                        <?= $totalTurmas ?>
                    </strong>

                </div>

            </article>


            <article class="card-estatistica">

                <span class="icone">
                    🎮
                </span>

                <div>

                    <span class="titulo">
                        Partidas
                    </span>

                    <strong>
                        <?= $totalPartidas ?>
                    </strong>

                </div>

            </article>


            <article class="card-estatistica">

                <span class="icone">
                    📈
                </span>

                <div>

                    <span class="titulo">
                        Média de acertos
                    </span>

                    <strong>
                        <?= number_format($mediaAcertos, 1, ',', '.') ?>%
                    </strong>

                </div>

            </article>

        </section>


        <!-- =========================
             ATALHOS
        ========================== -->

        <section class="secao">

            <div class="titulo-secao">

                <div>
                    <h2>Acesso rápido</h2>
                    <p>Gerencie os principais recursos.</p>
                </div>

            </div>


            <div class="atalhos">

                <a
                    href="turmas.php"
                    class="atalho"
                >
                    <span>🏫</span>
                    <strong>Turmas</strong>
                    <small>Visualizar turmas</small>
                </a>


                <a
                    href="alunos.php"
                    class="atalho"
                >
                    <span>👥</span>
                    <strong>Alunos</strong>
                    <small>Consultar alunos</small>
                </a>


                <a
                    href="desempenho.php"
                    class="atalho"
                >
                    <span>📊</span>
                    <strong>Desempenho</strong>
                    <small>Acompanhar resultados</small>
                </a>


                <a
                    href="relatorios.php"
                    class="atalho"
                >
                    <span>📄</span>
                    <strong>Relatórios</strong>
                    <small>Consultar relatórios</small>
                </a>

            </div>

        </section>


        <!-- =========================
             TURMAS
        ========================== -->

        <section class="secao">

            <div class="titulo-secao">

                <div>
                    <h2>Minhas turmas</h2>
                    <p>Resumo das turmas cadastradas.</p>
                </div>

                <a href="turmas.php">
                    Ver todas
                </a>

            </div>


            <?php if (empty($turmas)): ?>

                <div class="estado-vazio">

                    <span>🏫</span>

                    <h3>
                        Nenhuma turma encontrada
                    </h3>

                    <p>
                        Ainda não existem turmas disponíveis.
                    </p>

                </div>

            <?php else: ?>

                <div class="lista-turmas">

                    <?php foreach ($turmas as $turma): ?>

                        <article class="card-turma">

                            <div>

                                <span class="serie">
                                    <?= htmlspecialchars($turma['ano_serie']) ?>º ano
                                </span>

                                <h3>
                                    <?= htmlspecialchars($turma['nome']) ?>
                                </h3>

                            </div>

                            <div class="quantidade-alunos">

                                <strong>
                                    <?= (int) $turma['total_alunos'] ?>
                                </strong>

                                <span>
                                    aluno(s)
                                </span>

                            </div>

                            <a
                                href="alunos.php?turma=<?= (int) $turma['id'] ?>"
                                class="botao-secundario"
                            >
                                Ver alunos
                            </a>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>


        <!-- =========================
             ÚLTIMAS PARTIDAS
        ========================== -->

        <section class="secao">

            <div class="titulo-secao">

                <div>
                    <h2>Atividade recente</h2>
                    <p>Últimas partidas realizadas pelos alunos.</p>
                </div>

            </div>


            <?php if (empty($ultimasPartidas)): ?>

                <div class="estado-vazio">

                    <span>🎮</span>

                    <h3>
                        Nenhuma partida registrada
                    </h3>

                    <p>
                        As atividades dos alunos aparecerão aqui.
                    </p>

                </div>

            <?php else: ?>

                <div class="tabela-container">

                    <table>

                        <thead>

                            <tr>
                                <th>Aluno</th>
                                <th>Jogo</th>
                                <th>Acertos</th>
                                <th>Erros</th>
                                <th>Pontuação</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($ultimasPartidas as $partida): ?>

                                <tr>

                                    <td>
                                        <?= htmlspecialchars($partida['aluno']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($partida['jogo']) ?>
                                    </td>

                                    <td>
                                        <?= (int) $partida['acertos'] ?>
                                    </td>

                                    <td>
                                        <?= (int) $partida['erros'] ?>
                                    </td>

                                    <td>
                                        <?= (int) $partida['pontuacao'] ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>

</html>