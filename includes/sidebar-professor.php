<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nomeProfessor = $_SESSION['nome'] ?? 'Professor';
$tipoProfessor = $_SESSION['tipo'] ?? '';
?>

<aside class="sidebar-professor">

    <div class="sidebar-logo">

        <div class="logo-icone">
            🧮
        </div>

        <div>
            <strong>MathPlay</strong>
            <span>Área do Professor</span>
        </div>

    </div>


    <nav class="sidebar-menu">

        <a
            href="dashboard.php"
            class="sidebar-link ativo"
        >
            <span>🏠</span>
            <span>Dashboard</span>
        </a>


        <a
            href="turmas.php"
            class="sidebar-link"
        >
            <span>🏫</span>
            <span>Turmas</span>
        </a>


        <a
            href="alunos.php"
            class="sidebar-link"
        >
            <span>👨‍🎓</span>
            <span>Alunos</span>
        </a>


        <a
            href="desempenho.php"
            class="sidebar-link"
        >
            <span>📊</span>
            <span>Desempenho</span>
        </a>


        <a
            href="relatorios.php"
            class="sidebar-link"
        >
            <span>📋</span>
            <span>Relatórios</span>
        </a>

    </nav>


    <div class="sidebar-rodape">

        <div class="sidebar-usuario">

            <div class="avatar-professor">
                <?= strtoupper(substr($nomeProfessor, 0, 1)) ?>
            </div>

            <div class="dados-professor">

                <strong>
                    <?= htmlspecialchars($nomeProfessor) ?>
                </strong>

                <span>
                    <?= $tipoProfessor === 'admin' ? 'Administrador' : 'Professor' ?>
                </span>

            </div>

        </div>


        <div class="sidebar-divisor"></div>


        <a
            href="../logout.php"
            class="sidebar-logout"
        >
            <span>🚪</span>
            <span>Sair</span>
        </a>

    </div>

</aside>