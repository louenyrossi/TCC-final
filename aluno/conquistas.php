<?php

require_once '../includes/auth.php';
require_once '../config/config.php';

protegerPagina('aluno');

$usuarioId = usuarioId();

if (!$usuarioId) {
    header('Location: ../login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| DADOS DO ALUNO
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        u.nome,
        u.xp,
        u.nivel,
        t.nome AS turma_nome
    FROM usuarios u
    LEFT JOIN turmas t
        ON t.id = u.turma_id
    WHERE u.id = ?
    LIMIT 1
");

$stmt->execute([$usuarioId]);

$aluno = $stmt->fetch();

if (!$aluno) {
    die('Aluno não encontrado.');
}

/*
|--------------------------------------------------------------------------
| CONQUISTAS
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
    LEFT JOIN usuario_conquistas uc
        ON uc.conquista_id = c.id
        AND uc.usuario_id = ?
    ORDER BY
        CASE
            WHEN uc.id IS NOT NULL THEN 0
            ELSE 1
        END,
        c.id ASC
");

$stmt->execute([$usuarioId]);

$conquistas = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| CONTADORES
|--------------------------------------------------------------------------
*/

$totalConquistas = count($conquistas);
$desbloqueadas = 0;

foreach ($conquistas as $conquista) {
    if (!empty($conquista['data_desbloqueio'])) {
        $desbloqueadas++;
    }
}

$porcentagem = $totalConquistas > 0
    ? round(($desbloqueadas / $totalConquistas) * 100)
    : 0;

/*
|--------------------------------------------------------------------------
| ÍCONES DAS CONQUISTAS
|--------------------------------------------------------------------------
*/

function iconeConquista(string $nome): string
{
    $icones = [
        'Primeira Vitória' => '🥇',
        'Mestre da Matemática' => '🧠',
        'Caixa Rápido' => '⚡',
        'Memória de Elefante' => '🐘',
        'Aluno Dedicado' => '📚'
    ];

    return $icones[$nome] ?? '🏆';
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Conquistas - MathPower</title>

    <link
        rel="stylesheet"
        href="../assets/css/conquista-aluno.css"
    >

</head>

<body>
<div class="layout">

    <!-- MENU LATERAL -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <span class="logo-icone">🧮</span>
            <span>Math<span class="logo-destaque">Power</span></span>
        </div>

        <div class="sidebar-menu">
            <p class="menu-titulo">MENU PRINCIPAL</p>

            <a href="dashboard.php" class="menu-item">
                <span>🏠</span>
                <span>Dashboard</span>
            </a>

            <a href="trilha.php" class="menu-item">
                <span>🗺️</span>
                <span>Minha Trilha</span>
            </a>

            <a href="jogos.php" class="menu-item">
                <span>🎮</span>
                <span>Jogos</span>
            </a>

            <a href="conquistas.php" class="menu-item ativo">
                <span>🏆</span>
                <span>Conquistas</span>
            </a>
        </div>

        <div class="sidebar-bottom">
            <div class="sidebar-user">
                <div class="user-avatar">
                    <?= htmlspecialchars(
                        mb_strtoupper(
                            mb_substr($aluno['nome'], 0, 1)
                        )
                    ) ?>
                </div>

                <div class="user-info">
                    <strong><?= htmlspecialchars($aluno['nome']) ?></strong>
                    <span>Nível <?= (int) $aluno['nivel'] ?></span>
                </div>
            </div>

            <a href="../logout.php" class="sair">
                ↪ Sair da conta
            </a>
        </div>
    </aside>

    <!-- ÁREA PRINCIPAL -->
    <main class="main-content">

        <header class="topbar">
            <div>
                <span class="topbar-subtitulo">SUA JORNADA</span>
                <h1>Minhas conquistas 🏆</h1>
                <p>Veja suas medalhas e acompanhe sua evolução!</p>
            </div>

            <a href="dashboard.php" class="botao-voltar">
                ← Dashboard
            </a>
        </header>

        <!-- BOAS-VINDAS -->
        <section class="boas-vindas">
            <div>
                <span class="boas-tag">⭐ Continue evoluindo</span>
                <h2>Você está construindo sua jornada!</h2>
                <p>
                    Cada desafio concluído é uma oportunidade
                    de aprender e conquistar novas medalhas.
                </p>
            </div>

            <div class="boas-icone">🏆</div>
        </section>

        <!-- ESTATÍSTICAS -->
        <section class="resumo">

            <article class="resumo-card card-azul">
                <div class="resumo-icone">🥇</div>
                <span>Conquistas desbloqueadas</span>
                <strong><?= $desbloqueadas ?></strong>
                <small>Medalhas conquistadas</small>
            </article>

            <article class="resumo-card card-roxo">
                <div class="resumo-icone">🏆</div>
                <span>Total de conquistas</span>
                <strong><?= $totalConquistas ?></strong>
                <small>Disponíveis na plataforma</small>
            </article>

            <article class="resumo-card card-verde">
                <div class="resumo-icone">📈</div>
                <span>Progresso geral</span>
                <strong><?= $porcentagem ?>%</strong>
                <small>Continue avançando!</small>
            </article>

        </section>

        <!-- BARRA DE PROGRESSO -->
        <section class="progresso-card">
            <div class="progresso-topo">
                <div>
                    <h2>Seu progresso</h2>
                    <p>Você está cada vez mais perto da próxima conquista.</p>
                </div>

                <strong><?= $desbloqueadas ?>/<?= $totalConquistas ?></strong>
            </div>

            <div
                class="barra"
                role="progressbar"
                aria-valuenow="<?= $porcentagem ?>"
                aria-valuemin="0"
                aria-valuemax="100"
            >
                <div
                    class="barra-preenchida"
                    style="width: <?= $porcentagem ?>%"
                ></div>
            </div>
        </section>

        <!-- LISTA DE CONQUISTAS -->
        <section class="secao-conquistas">
            <div class="secao-titulo">
                <div>
                    <h2>Galeria de conquistas</h2>
                    <p>Suas medalhas e os próximos objetivos.</p>
                </div>

                <span class="contador">
                    <?= $desbloqueadas ?> de <?= $totalConquistas ?>
                </span>
            </div>

            <?php if (empty($conquistas)): ?>

                <div class="vazio">
                    <div class="vazio-icone">🏆</div>
                    <h3>Nenhuma conquista cadastrada</h3>
                    <p>
                        As conquistas aparecerão aqui quando
                        forem cadastradas na plataforma.
                    </p>
                </div>

            <?php else: ?>

                <div class="conquistas-grid">
                    <?php foreach ($conquistas as $conquista): ?>

                        <?php
                        $desbloqueada = !empty(
                            $conquista['data_desbloqueio']
                        );
                        ?>

                        <article class="conquista <?= $desbloqueada ? 'conquista-liberada' : 'bloqueada' ?>">

                            <div class="conquista-icone">
                                <?= iconeConquista($conquista['nome']) ?>
                            </div>

                            <div class="conquista-info">
                                <div class="conquista-titulo">
                                    <h3>
                                        <?= htmlspecialchars($conquista['nome']) ?>
                                    </h3>
                                    <span class="status <?= $desbloqueada ? 'status-desbloqueada' : 'status-bloqueada' ?>">
                                        <?= $desbloqueada ? '✓ Conquistada' : '🔒 Bloqueada' ?>
                                    </span>
                                </div>

                                <p>
                                    <?= htmlspecialchars($conquista['descricao']) ?>
                                </p>

                                <?php if ($desbloqueada): ?>
                                    <span class="data">
                                        Conquistada em
                                        <?= date(
                                            'd/m/Y',
                                            strtotime($conquista['data_desbloqueio'])
                                        ) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="data">
                                        Continue jogando para desbloquear
                                    </span>
                                <?php endif; ?>
                            </div>

                        </article>

                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </section>

        <footer class="rodape">
            MathPower · Aprender também é conquistar! ✨
        </footer>

    </main>
</div>
</body>

</html>