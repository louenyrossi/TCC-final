<?php
require_once '../includes/auth.php';
require_once '../config/config.php';

protegerPagina('aluno');

$usuarioId = usuarioId();

$stmt = $pdo->prepare("
    SELECT
        u.nome,
        u.nivel,
        u.xp,
        u.turma_id,
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

$turmaId = (int) ($aluno['turma_id'] ?? 0);

/*
|--------------------------------------------------------------------------
| Busca os jogos disponíveis para a turma
|--------------------------------------------------------------------------
*/
if ($turmaId > 0) {

    $stmt = $pdo->prepare("
        SELECT
            j.id,
            j.nome,
            j.descricao,
            j.tema,

            COALESCE(
                (
                    SELECT p.status
                    FROM progresso p
                    WHERE p.usuario_id = ?
                      AND p.jogo_id = j.id
                      AND p.dificuldade = 'facil'
                    LIMIT 1
                ),
                'bloqueado'
            ) AS status,

            COALESCE(
                (
                    SELECT p.porcentagem
                    FROM progresso p
                    WHERE p.usuario_id = ?
                      AND p.jogo_id = j.id
                      AND p.dificuldade = 'facil'
                    LIMIT 1
                ),
                0
            ) AS porcentagem

        FROM jogos j

        INNER JOIN jogos_turmas jt
            ON jt.jogo_id = j.id
            AND jt.turma_id = ?
            AND jt.ativo = 1

        WHERE j.ativo = 1

        ORDER BY j.id ASC
    ");

    $stmt->execute([
        $usuarioId,
        $usuarioId,
        $turmaId
    ]);

} else {

    /*
    |--------------------------------------------------------------------------
    | Caso o aluno ainda não tenha turma
    |--------------------------------------------------------------------------
    */
    $stmt = $pdo->prepare("
        SELECT
            j.id,
            j.nome,
            j.descricao,
            j.tema,

            COALESCE(p.status, 'disponivel') AS status,
            COALESCE(p.porcentagem, 0) AS porcentagem

        FROM jogos j

        LEFT JOIN progresso p
            ON p.jogo_id = j.id
            AND p.usuario_id = ?
            AND p.dificuldade = 'facil'

        WHERE j.ativo = 1

        ORDER BY j.id ASC
    ");

    $stmt->execute([$usuarioId]);
}

$jogos = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Organiza os estados da trilha
|--------------------------------------------------------------------------
*/
foreach ($jogos as $index => &$jogo) {

    $jogo['porcentagem'] = max(
        0,
        min(100, (int) $jogo['porcentagem'])
    );

    /*
    |--------------------------------------------------------------------------
    | Primeiro jogo disponível
    |--------------------------------------------------------------------------
    */
    if ($index === 0 && $jogo['status'] === 'bloqueado') {
        $jogo['status'] = 'disponivel';
    }

    /*
    |--------------------------------------------------------------------------
    | Libera o próximo jogo após conclusão
    |--------------------------------------------------------------------------
    */
    if ($index > 0 && $jogo['status'] === 'bloqueado') {

        $jogoAnterior = $jogos[$index - 1];

        if ($jogoAnterior['status'] === 'concluido') {
            $jogo['status'] = 'disponivel';
        }
    }
}

unset($jogo);


/*
|--------------------------------------------------------------------------
| Funções auxiliares
|--------------------------------------------------------------------------
*/
function iconeJogo(string $nome): string
{
    $nome = mb_strtolower($nome);

    if (str_contains($nome, 'caixa')) {
        return '💰';
    }

    if (str_contains($nome, 'memória')) {
        return '🧠';
    }

    return '🎮';
}

function classeStatus(string $status): string
{
    return match ($status) {
        'concluido' => 'completed',
        'em_andamento' => 'current',
        'disponivel' => 'available',
        default => 'locked'
    };
}

function textoStatus(string $status): string
{
    return match ($status) {
        'concluido' => 'Concluído',
        'em_andamento' => 'Em andamento',
        'disponivel' => 'Disponível',
        default => 'Bloqueado'
    };
}

function linkJogo(int $jogoId): string
{
    return match ($jogoId) {
        1 => '../jogos/caixa-matematico/index.php',
        2 => '../jogos/memoria-matematica/index.php',
        default => 'jogos.php'
    };
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

    <title>Trilha de Aprendizagem | MathPlay</title>

    <link
        rel="stylesheet"
        href="../assets/css/trilha.css"
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
                class="nav-item active"
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
                class="nav-item"
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
                    Trilha de Aprendizagem
                </strong>

            </div>


            <div class="student-mini">

                <div class="student-avatar">

                    <?= htmlspecialchars(
                        strtoupper(
                            mb_substr($aluno['nome'], 0, 1)
                        )
                    ) ?>

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
                        SEU CAMINHO
                    </span>

                    <h1>
                        Trilha de Aprendizagem 🛤️
                    </h1>

                    <p>
                        Avance pelos desafios e acompanhe
                        sua evolução em Matemática.
                    </p>

                </div>


                <div class="level-card">

                    <span>
                        Nível atual
                    </span>

                    <strong>
                        <?= (int) $aluno['nivel'] ?>
                    </strong>

                    <small>
                        <?= (int) $aluno['xp'] ?> XP
                    </small>

                </div>

            </div>


            <!-- TURMA -->
            <div class="class-info">

                <div class="class-icon">
                    🎓
                </div>

                <div>

                    <span>
                        Minha turma
                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            $aluno['turma']
                            ?: 'Turma não definida'
                        ) ?>

                    </strong>

                </div>

            </div>


            <!-- TRILHA -->
            <section class="learning-path">

                <?php if (!$jogos): ?>

                    <div class="empty-state">

                        <div class="empty-icon">
                            📚
                        </div>

                        <h2>
                            Sua trilha ainda está sendo preparada
                        </h2>

                        <p>
                            Quando novos jogos forem disponibilizados,
                            eles aparecerão aqui.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="path-line"></div>


                    <?php foreach ($jogos as $index => $jogo): ?>

                        <?php

                        $status = $jogo['status'];

                        $classe = classeStatus($status);

                        $texto = textoStatus($status);

                        $porcentagem =
                            (int) $jogo['porcentagem'];

                        $bloqueado =
                            $status === 'bloqueado';

                        ?>


                        <article
                            class="path-item <?= $classe ?>"
                        >

                            <!-- NÓ DA TRILHA -->
                            <div class="path-node">

                                <?php if ($status === 'concluido'): ?>

                                    ✓

                                <?php elseif ($bloqueado): ?>

                                    🔒

                                <?php else: ?>

                                    <?= $index + 1 ?>

                                <?php endif; ?>

                            </div>


                            <!-- CARD -->
                            <div class="path-card">

                                <div class="path-card-header">

                                    <div class="game-icon">

                                        <?= iconeJogo(
                                            $jogo['nome']
                                        ) ?>

                                    </div>


                                    <div class="game-title">

                                        <span class="step-label">
                                            ETAPA <?= $index + 1 ?>
                                        </span>

                                        <h2>

                                            <?= htmlspecialchars(
                                                $jogo['nome']
                                            ) ?>

                                        </h2>

                                    </div>


                                    <span class="status-badge">

                                        <?= $texto ?>

                                    </span>

                                </div>


                                <p class="game-description">

                                    <?= htmlspecialchars(
                                        $jogo['descricao']
                                        ?: 'Complete este desafio para avançar na sua trilha.'
                                    ) ?>

                                </p>


                                <!-- PROGRESSO -->
                                <div class="progress-area">

                                    <div class="progress-label">

                                        <span>
                                            Progresso
                                        </span>

                                        <strong>
                                            <?= $porcentagem ?>%
                                        </strong>

                                    </div>


                                    <div class="progress-bar">

                                        <div
                                            class="progress-fill"
                                            style="width: <?= $porcentagem ?>%"
                                        ></div>

                                    </div>

                                </div>


                                <?php if (!$bloqueado): ?>

                                    <a
                                        href="<?= htmlspecialchars(
                                            linkJogo(
                                                (int) $jogo['id']
                                            )
                                        ) ?>"
                                        class="path-button"
                                    >

                                        <?php

                                        if ($status === 'concluido') {
                                            echo 'Jogar novamente';
                                        } elseif ($status === 'em_andamento') {
                                            echo 'Continuar';
                                        } else {
                                            echo 'Começar';
                                        }

                                        ?>

                                        <span>
                                            →
                                        </span>

                                    </a>

                                <?php else: ?>

                                    <div class="locked-message">

                                        🔒 Complete as etapas anteriores
                                        para liberar este desafio.

                                    </div>

                                <?php endif; ?>

                            </div>

                        </article>

                    <?php endforeach; ?>

                <?php endif; ?>

            </section>

        </section>

    </main>

</div>


<div
    class="sidebar-overlay"
    id="sidebar-overlay"
></div>


<script src="../assets/js/trilha.js"></script>

</body>

</html>