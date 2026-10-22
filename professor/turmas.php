
<?php
require_once '../includes/auth.php';
require_once '../config/config.php';

protegerPagina(['professor', 'admin']);

$idTurma = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idTurma) {
    header('Location: turmas.php');
    exit;
}

// Buscar turma
$stmt = $pdo->prepare("
    SELECT id, nome, ano_serie
    FROM turmas
    WHERE id = ? AND status = 'ativa'
");
$stmt->execute([$idTurma]);
$turma = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$turma) {
    header('Location: turmas.php');
    exit;
}

// Buscar alunos da turma
$stmt = $pdo->prepare("
    SELECT id, nome, email, nivel, xp
    FROM usuarios
    WHERE turma_id = ? AND tipo = 'aluno'
    ORDER BY nome ASC
");
$stmt->execute([$idTurma]);
$alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalAlunos = count($alunos);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($turma['nome']) ?> | MathPower</title>

    <!-- Reutiliza o CSS do dashboard -->
    <link rel="stylesheet" href="../assets/css/prof-dashboard.css">

    <!-- CSS específico da página da turma -->
    <link rel="stylesheet" href="../assets/css/prof-turmas.css">
</head>
<body>

<div class="dashboard-container">

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="logo">
                <strong>Math<span>Power</span></strong>
            </a>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">
                <span class="nav-icon">⌂</span>
                <span>Dashboard</span>
            </a>

            <a href="turmas.php" class="nav-item active">
                <span class="nav-icon">▦</span>
                <span>Turmas</span>
            </a>

            <a href="alunos.php" class="nav-item">
                <span class="nav-icon">♙</span>
                <span>Alunos</span>
            </a>

            <a href="desempenho.php" class="nav-item">
                <span class="nav-icon">▥</span>
                <span>Desempenho</span>
            </a>

            <a href="relatorios.php" class="nav-item">
                <span class="nav-icon">▤</span>
                <span>Relatórios</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="../logout.php" class="nav-item">
                <span class="nav-icon">⇥</span>
                <span>Sair</span>
            </a>
        </div>
    </aside>

    <!-- Conteúdo principal -->
    <main class="main-content">

        <!-- Barra superior -->
        <header class="topbar">
            <div class="topbar-left">
                <button
                    type="button"
                    class="menu-toggle"
                    id="menuToggle"
                    aria-label="Abrir menu"
                >
                    ☰
                </button>

                <div class="topbar-title">Alunos da turma</div>
            </div>
        </header>

        <div class="content">

            <!-- Cabeçalho da página -->
            <section class="page-header turma-page-header">
                <div class="turma-heading">
                    <a href="turmas.php" class="back-link">
                        ← Voltar para turmas
                    </a>

                    <p class="eyebrow">TURMA</p>

                    <h1><?= htmlspecialchars($turma['nome']) ?></h1>

                    <p class="page-description">
                        <?= htmlspecialchars($turma['ano_serie'] ?? '') ?>
                        <?php if (!empty($turma['ano_serie'])): ?>
                            <span class="description-separator">·</span>
                        <?php endif; ?>
                        <?= $totalAlunos ?>
                        <?= $totalAlunos === 1 ? 'aluno matriculado' : 'alunos matriculados' ?>
                    </p>
                </div>
            </section>

            <!-- Lista de alunos -->
            <section class="section turma-students-section">
                <div class="section-header">
                    <div>
                        <h2>Alunos</h2>
                        <p>Confira os alunos que fazem parte desta turma.</p>
                    </div>

                    <span class="student-count">
                        <?= $totalAlunos ?>
                    </span>
                </div>

                <?php if (empty($alunos)): ?>

                    <div class="empty-card">
                        <div class="empty-icon">♙</div>
                        <h3>Nenhum aluno nesta turma</h3>
                        <p>
                            Quando houver alunos vinculados a esta turma,
                            eles aparecerão aqui.
                        </p>
                    </div>

                <?php else: ?>

                    <div class="students-grid">
                        <?php foreach ($alunos as $aluno): ?>
                            <a
                                href="aluno.php?id=<?= (int) $aluno['id'] ?>"
                                class="student-card"
                            >
                                <div class="student-avatar">
                                    <?= htmlspecialchars(
                                        mb_strtoupper(
                                            mb_substr($aluno['nome'], 0, 1, 'UTF-8'),
                                            'UTF-8'
                                        )
                                    ) ?>
                                </div>

                                <div class="student-info">
                                    <h3>
                                        <?= htmlspecialchars($aluno['nome']) ?>
                                    </h3>

                                    <p class="student-email">
                                        <?= htmlspecialchars($aluno['email'] ?? '') ?>
                                    </p>

                                    <div class="student-meta">
                                        <span>
                                            Nível <?= (int) ($aluno['nivel'] ?? 1) ?>
                                        </span>

                                        <span>
                                            <?= (int) ($aluno['xp'] ?? 0) ?> XP
                                        </span>
                                    </div>
                                </div>

                                <span class="student-arrow" aria-hidden="true">
                                    →
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>

                <?php endif; ?>
            </section>

        </div>
    </main>
</div>

<script src="../assets/js/prof-dashboard.js"></script>
<script src="../assets/js/prof-turmas.js"></script>

</body>
</html>
