
<?php
require_once '../includes/auth.php';
require_once '../config/config.php';

protegerPagina(['professor', 'admin']);

$usuarioProfessorId = usuarioId();

/*
|--------------------------------------------------------------------------
| TURMAS DISPONÍVEIS
|--------------------------------------------------------------------------
*/
$stmtTurmas = $pdo->query("
    SELECT id, nome, ano_serie
    FROM turmas
    WHERE ativo = 1
    ORDER BY ano_serie ASC, nome ASC
");

$turmas = $stmtTurmas->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| JOGOS DISPONÍVEIS
|--------------------------------------------------------------------------
*/
$stmtJogos = $pdo->query("
    SELECT id, nome
    FROM jogos
    WHERE ativo = 1
    ORDER BY id ASC
");

$jogos = $stmtJogos->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| FILTROS
|--------------------------------------------------------------------------
*/
$turmaSelecionada = filter_input(
    INPUT_GET,
    'turma',
    FILTER_VALIDATE_INT
);

$jogoSelecionado = filter_input(
    INPUT_GET,
    'jogo',
    FILTER_VALIDATE_INT
);

if (!$turmaSelecionada || $turmaSelecionada < 1) {
    $turmaSelecionada = null;
}

if (!$jogoSelecionado || $jogoSelecionado < 1) {
    $jogoSelecionado = null;
}

/*
|--------------------------------------------------------------------------
| VALIDA TURMA
|--------------------------------------------------------------------------
*/
if ($turmaSelecionada !== null) {
    $stmtValidacao = $pdo->prepare("
        SELECT id
        FROM turmas
        WHERE id = ?
          AND ativo = 1
        LIMIT 1
    ");

    $stmtValidacao->execute([$turmaSelecionada]);

    if (!$stmtValidacao->fetchColumn()) {
        $turmaSelecionada = null;
    }
}

/*
|--------------------------------------------------------------------------
| VALIDA JOGO
|--------------------------------------------------------------------------
*/
if ($jogoSelecionado !== null) {
    $stmtValidacao = $pdo->prepare("
        SELECT id
        FROM jogos
        WHERE id = ?
          AND ativo = 1
        LIMIT 1
    ");

    $stmtValidacao->execute([$jogoSelecionado]);

    if (!$stmtValidacao->fetchColumn()) {
        $jogoSelecionado = null;
    }
}

/*
|--------------------------------------------------------------------------
| CONSULTA DOS ALUNOS E RESULTADOS
|--------------------------------------------------------------------------
*/
$where = ["u.tipo = 'aluno'"];
$params = [];

if ($turmaSelecionada !== null) {
    $where[] = "u.turma_id = ?";
    $params[] = $turmaSelecionada;
}

$whereSql = implode(' AND ', $where);

$sql = "
    SELECT
        u.id,
        u.nome,
        u.nivel,
        u.xp,
        t.nome AS turma,
        t.ano_serie,

        COUNT(p.id) AS partidas,

        COALESCE(SUM(p.acertos), 0) AS acertos,
        COALESCE(SUM(p.erros), 0) AS erros,
        COALESCE(SUM(p.pontuacao), 0) AS pontuacao

    FROM usuarios u

    LEFT JOIN turmas t
        ON t.id = u.turma_id

    LEFT JOIN partidas p
        ON p.usuario_id = u.id
        " . ($jogoSelecionado !== null
            ? "AND p.jogo_id = ?"
            : "") . "

    WHERE {$whereSql}

    GROUP BY
        u.id,
        u.nome,
        u.nivel,
        u.xp,
        t.nome,
        t.ano_serie

    ORDER BY
        t.ano_serie ASC,
        t.nome ASC,
        u.nome ASC
";

/*
 * O parâmetro do jogo vem primeiro porque o placeholder dele
 * aparece no JOIN, antes dos parâmetros do WHERE.
 */
if ($jogoSelecionado !== null) {
    array_unshift($params, $jogoSelecionado);
}

$stmtRelatorio = $pdo->prepare($sql);
$stmtRelatorio->execute($params);

$alunos = $stmtRelatorio->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| RESUMO DO RELATÓRIO
|--------------------------------------------------------------------------
*/
$totalAlunos = count($alunos);
$totalPartidas = 0;
$totalAcertos = 0;
$totalErros = 0;
$totalPontuacao = 0;

foreach ($alunos as $aluno) {
    $totalPartidas += (int) $aluno['partidas'];
    $totalAcertos += (int) $aluno['acertos'];
    $totalErros += (int) $aluno['erros'];
    $totalPontuacao += (int) $aluno['pontuacao'];
}

$totalRespostas = $totalAcertos + $totalErros;

$precisaoGeral = $totalRespostas > 0
    ? (int) round(($totalAcertos / $totalRespostas) * 100)
    : 0;

/*
|--------------------------------------------------------------------------
| NOMES DOS FILTROS SELECIONADOS
|--------------------------------------------------------------------------
*/
$nomeTurmaRelatorio = 'Todas as turmas';
$nomeJogoRelatorio = 'Todos os jogos';

foreach ($turmas as $turma) {
    if (
        $turmaSelecionada !== null
        && (int) $turma['id'] === (int) $turmaSelecionada
    ) {
        $nomeTurmaRelatorio =
            $turma['ano_serie'] . 'º ano — ' . $turma['nome'];

        break;
    }
}

foreach ($jogos as $jogo) {
    if (
        $jogoSelecionado !== null
        && (int) $jogo['id'] === (int) $jogoSelecionado
    ) {
        $nomeJogoRelatorio = $jogo['nome'];
        break;
    }
}

$dataRelatorio = date('d/m/Y H:i');

/*
|--------------------------------------------------------------------------
| FUNÇÃO PARA ESCAPAR TEXTO HTML
|--------------------------------------------------------------------------
*/
function e($valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
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

    <title>Relatórios - MathPower</title>

    <link rel="stylesheet" href="../assets/css/prof-dashboard.css">
    <link rel="stylesheet" href="../assets/css/prof-relatorios.css">
    <link rel="stylesheet" href="../assets/css/acessibilidade.css">
</head>

<body>
<div class="app">

    <!-- MENU LATERAL -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <div class="logo-icon">M</div>

                <div>
                    <strong>Math<span>Power</span></strong>
                    <small>Área do Professor</small>
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

            <a href="alunos.php" class="nav-item">
                <span class="nav-icon">🎓</span>
                <span>Alunos</span>
            </a>

            <a href="desempenho.php" class="nav-item">
                <span class="nav-icon">📊</span>
                <span>Desempenho</span>
            </a>

            <a
                href="relatorios.php"
                class="nav-item active"
                aria-current="page"
            >
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

    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
    ></div>

    <!-- CONTEÚDO PRINCIPAL -->
    <main class="main-content">
        <header class="topbar">
            <button
                type="button"
                class="menu-button"
                id="menuButton"
                aria-label="Abrir menu"
                aria-controls="sidebar"
                aria-expanded="false"
            >
                ☰
            </button>

            <div class="topbar-title">
                <span>Área do Professor</span>
                <strong>Relatórios</strong>
            </div>
        </header>

        <section class="content">

            <!-- CABEÇALHO -->
            <div class="page-header">
                <div>
                    <span class="eyebrow">Relatórios</span>

                    <h1>Relatório de desempenho</h1>

                    <p>
                        Consulte e organize os resultados dos alunos.
                    </p>
                </div>

                <button
                    type="button"
                    class="print-button"
                    id="printButton"
                >
                    🖨️ Imprimir relatório
                </button>
            </div>

            <!-- FILTROS -->
            <form method="GET" class="filters">
                <div class="filter-group">
                    <label for="turma">Turma</label>

                    <select name="turma" id="turma">
                        <option value="">Todas as turmas</option>

                        <?php foreach ($turmas as $turma): ?>
                            <option
                                value="<?= (int) $turma['id'] ?>"
                                <?= (
                                    $turmaSelecionada !== null
                                    && (int) $turmaSelecionada
                                        === (int) $turma['id']
                                ) ? 'selected' : '' ?>
                            >
                                <?= e($turma['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="jogo">Jogo</label>

                    <select name="jogo" id="jogo">
                        <option value="">Todos os jogos</option>

                        <?php foreach ($jogos as $jogo): ?>
                            <option
                                value="<?= (int) $jogo['id'] ?>"
                                <?= (
                                    $jogoSelecionado !== null
                                    && (int) $jogoSelecionado
                                        === (int) $jogo['id']
                                ) ? 'selected' : '' ?>
                            >
                                <?= e($jogo['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="filter-button">
                        Gerar relatório
                    </button>

                    <a
                        href="relatorios.php"
                        class="clear-button"
                    >
                        Limpar
                    </a>
                </div>
            </form>

            <!-- RELATÓRIO -->
            <section class="report-area" id="reportArea">
                <div class="report-header">
                    <div>
                        <span class="report-label">MATHPOWER</span>

                        <h2>Relatório de desempenho</h2>

                        <p>Acompanhamento dos estudantes</p>
                    </div>

                    <div class="report-date">
                        <span>Gerado em</span>
                        <strong><?= e($dataRelatorio) ?></strong>
                    </div>
                </div>

                <!-- FILTROS APLICADOS -->
                <div class="report-filters">
                    <div>
                        <span>Turma</span>
                        <strong><?= e($nomeTurmaRelatorio) ?></strong>
                    </div>

                    <div>
                        <span>Jogo</span>
                        <strong><?= e($nomeJogoRelatorio) ?></strong>
                    </div>
                </div>

                <!-- INDICADORES -->
                <div class="report-stats">
                    <div class="report-stat">
                        <span>Alunos</span>
                        <strong><?= $totalAlunos ?></strong>
                    </div>

                    <div class="report-stat">
                        <span>Partidas</span>
                        <strong><?= $totalPartidas ?></strong>
                    </div>

                    <div class="report-stat">
                        <span>Acertos</span>
                        <strong><?= $totalAcertos ?></strong>
                    </div>

                    <div class="report-stat">
                        <span>Erros</span>
                        <strong><?= $totalErros ?></strong>
                    </div>

                    <div class="report-stat">
                        <span>Precisão geral</span>
                        <strong><?= $precisaoGeral ?>%</strong>
                    </div>

                    <div class="report-stat">
                        <span>Pontuação total</span>
                        <strong>
                            <?= number_format(
                                $totalPontuacao,
                                0,
                                ',',
                                '.'
                            ) ?>
                        </strong>
                    </div>
                </div>

                <!-- TABELA DE RESULTADOS -->
                <div class="report-table">
                    <div class="table-title">
                        <div>
                            <h3>Desempenho dos alunos</h3>

                            <p>
                                Resultados registrados no sistema.
                            </p>
                        </div>
                    </div>

                    <?php if (empty($alunos)): ?>
                        <div class="empty-state">
                            <span>📄</span>

                            <strong>Nenhum dado encontrado</strong>

                            <p>
                                Não existem resultados para os filtros
                                selecionados.
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Aluno</th>
                                        <th>Turma</th>
                                        <th>Nível</th>
                                        <th>Partidas</th>
                                        <th>Acertos</th>
                                        <th>Erros</th>
                                        <th>Precisão</th>
                                        <th>Pontuação</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($alunos as $aluno): ?>
                                        <?php
                                        $acertosAluno =
                                            (int) $aluno['acertos'];

                                        $errosAluno =
                                            (int) $aluno['erros'];

                                        $totalRespostasAluno =
                                            $acertosAluno + $errosAluno;

                                        $precisaoAluno =
                                            $totalRespostasAluno > 0
                                                ? (int) round(
                                                    ($acertosAluno
                                                        / $totalRespostasAluno)
                                                    * 100
                                                )
                                                : 0;

                                        $nomeAluno = (string) $aluno['nome'];

                                        $inicialAluno = function_exists(
                                            'mb_substr'
                                        )
                                            ? mb_substr(
                                                $nomeAluno,
                                                0,
                                                1,
                                                'UTF-8'
                                            )
                                            : substr($nomeAluno, 0, 1);

                                        $inicialAluno = function_exists(
                                            'mb_strtoupper'
                                        )
                                            ? mb_strtoupper(
                                                $inicialAluno,
                                                'UTF-8'
                                            )
                                            : strtoupper($inicialAluno);
                                        ?>

                                        <tr>
                                            <td>
                                                <div class="student-cell">
                                                    <div class="student-avatar">
                                                        <?= e($inicialAluno) ?>
                                                    </div>

                                                    <strong>
                                                        <?= e($nomeAluno) ?>
                                                    </strong>
                                                </div>
                                            </td>

                                            <td>
                                                <?= e(
                                                    $aluno['turma']
                                                    ?? 'Sem turma'
                                                ) ?>
                                            </td>

                                            <td>
                                                <?= (int) $aluno['nivel'] ?>
                                            </td>

                                            <td>
                                                <?= (int) $aluno['partidas'] ?>
                                            </td>

                                            <td>
                                                <span class="correct">
                                                    <?= $acertosAluno ?>
                                                </span>
                                            </td>

                                            <td>
                                                <span class="wrong">
                                                    <?= $errosAluno ?>
                                                </span>
                                            </td>

                                            <td>
                                                <strong class="precision-value">
                                                    <?= $precisaoAluno ?>%
                                                </strong>
                                            </td>

                                            <td>
                                                <strong>
                                                    <?= number_format(
                                                        (int) $aluno['pontuacao'],
                                                        0,
                                                        ',',
                                                        '.'
                                                    ) ?>
                                                </strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="report-footer">
                    <span>MathPower — Plataforma Educacional</span>
                    <span>Relatório gerado pelo sistema</span>
                </div>
            </section>
        </section>
    </main>
</div>

<script src="../assets/js/prof-relatorios.js"></script>
<?php require_once __DIR__ . '/../includes/acessibilidade.php'; ?>
<script src="../assets/js/acessibilidade.js" defer></script>
</body>
</html>
