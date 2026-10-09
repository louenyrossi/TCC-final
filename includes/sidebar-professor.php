
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nomeProfessor = $_SESSION['nome'] ?? 'Professor';
$tipoProfessor = $_SESSION['tipo'] ?? '';
?>

<link rel="stylesheet" href="../assets/css/tema.css">
<script src="../assets/js/tema.js" defer></script>

<aside class="sidebar-professor">

    <div class="sidebar-logo">
        <div class="logo-icone">🧮</div>

        <div>
            <strong>MathPlay</strong>
            <span>Área do Professor</span>
        </div>
    </div>

    <nav class="sidebar-menu">

        <a href="dashboard.php" class="sidebar-link ativo">
            <span>🏠</span>
            <span>Dashboard</span>
        </a>

        <a href="turmas.php" class="sidebar-link">
            <span>🏫</span>
            <span>Turmas</span>
        </a>

        <a href="alunos.php" class="sidebar-link">
            <span>👨‍🎓</span>
            <span>Alunos</span>
        </a>

        <a href="desempenho.php" class="sidebar-link">
            <span>📊</span>
            <span>Desempenho</span>
        </a>

        <a href="relatorios.php" class="sidebar-link">
            <span>📋</span>
            <span>Relatórios</span>
        </a>

    </nav>

    <div class="sidebar-rodape">

       <button
    type="button"
    data-alternar-tema
    style="display:flex; padding:12px; margin:10px;
           background:#7C3AED; color:white;
           border:0; border-radius:8px; cursor:pointer;"
>
    🌙 Alternar tema
</button>

        <div class="sidebar-divisor"></div>

        <div class="sidebar-usuario">

            <div class="avatar-professor">
                <?= htmlspecialchars(
                    strtoupper(substr($nomeProfessor, 0, 1)),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

            <div class="dados-professor">
                <strong>
                    <?= htmlspecialchars($nomeProfessor, ENT_QUOTES, 'UTF-8') ?>
                </strong>

                <span>
                    <?= $tipoProfessor === 'admin'
                        ? 'Administrador'
                        : 'Professor' ?>
                </span>
            </div>

        </div>

        <div class="sidebar-divisor"></div>

        <a href="../logout.php" class="sidebar-logout">
            <span>🚪</span>
            <span>Sair</span>
        </a>

    </div>

</aside>
