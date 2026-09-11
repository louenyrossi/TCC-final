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

    <title>Conquistas - MathPlay</title>

    <link
        rel="stylesheet"
        href="../assets/css/conquistas.css"
    >

</head>

<body>

<div class="pagina">

    <!-- CABEÇALHO -->

    <header class="cabecalho">

        <div class="cabecalho-conteudo">

            <div class="marca">

                <div class="marca-icone">
                    🏆
                </div>

                <div class="marca-texto">

                    <strong>MathPlay</strong>

                    <span>
                        Conquistas
                    </span>

                </div>

            </div>

            <a
                href="dashboard.php"
                class="voltar"
            >
                ← Voltar
            </a>

        </div>

    </header>


    <!-- CONTEÚDO -->

    <main class="conteudo">

        <section class="titulo-area">

            <h1>
                Minhas conquistas 🏆
            </h1>

            <p>
                Continue jogando e aprendendo para desbloquear novas medalhas!
            </p>

        </section>


        <!-- RESUMO -->

        <section class="resumo">

            <div class="resumo-card">

                <span>
                    Conquistas desbloqueadas
                </span>

                <strong>
                    <?= $desbloqueadas ?>
                </strong>

            </div>


            <div class="resumo-card">

                <span>
                    Total de conquistas
                </span>

                <strong>
                    <?= $totalConquistas ?>
                </strong>

            </div>


            <div class="resumo-card">

                <span>
                    Progresso
                </span>

                <strong>
                    <?= $porcentagem ?>%
                </strong>

            </div>

        </section>


        <!-- PROGRESSO -->

        <section class="progresso-card">

            <div class="progresso-topo">

                <strong>
                    Progresso das conquistas
                </strong>

                <span>
                    <?= $desbloqueadas ?>/<?= $totalConquistas ?>
                </span>

            </div>

            <div class="barra">

                <div
                    class="barra-preenchida"
                    style="width: <?= $porcentagem ?>%;"
                ></div>

            </div>

        </section>


        <!-- LISTA DE CONQUISTAS -->

        <section>

            <div class="secao-titulo">

                <h2>
                    Todas as conquistas
                </h2>

                <p>
                    Algumas conquistas serão desbloqueadas conforme você evolui.
                </p>

            </div>


            <?php if (empty($conquistas)): ?>

                <div class="vazio">

                    <div class="vazio-icone">
                        🏆
                    </div>

                    <h3>
                        Nenhuma conquista cadastrada
                    </h3>

                    <p>
                        As conquistas aparecerão aqui quando forem cadastradas.
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

                        <article
                            class="conquista <?= !$desbloqueada ? 'bloqueada' : '' ?>"
                        >

                            <div class="conquista-icone">

                                <?= iconeConquista(
                                    $conquista['nome']
                                ) ?>

                            </div>


                            <div class="conquista-info">

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


                                <?php if ($desbloqueada): ?>

                                    <span class="status status-desbloqueada">
                                        ✓ Desbloqueada
                                    </span>

                                    <?php if (!empty($conquista['data_desbloqueio'])): ?>

                                        <span class="data">

                                            Desbloqueada em
                                            <?= date(
                                                'd/m/Y',
                                                strtotime(
                                                    $conquista['data_desbloqueio']
                                                )
                                            ) ?>

                                        </span>

                                    <?php endif; ?>

                                <?php else: ?>

                                    <span class="status status-bloqueada">
                                        🔒 Bloqueada
                                    </span>

                                <?php endif; ?>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>

</html>