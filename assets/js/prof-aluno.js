document.addEventListener('DOMContentLoaded', function () {

    const sidebar = document.getElementById('sidebar');
    const menuButton = document.getElementById('menuButton');
    const overlay = document.getElementById('sidebarOverlay');

    if (!sidebar || !menuButton || !overlay) {
        return;
    }

    function abrirMenu() {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function fecharMenu() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    menuButton.addEventListener('click', function () {
        if (sidebar.classList.contains('open')) {
            fecharMenu();
        } else {
            abrirMenu();
        }
    });

    overlay.addEventListener('click', fecharMenu);

    document.querySelectorAll('.nav-item, .logout-link').forEach(function (link) {
        link.addEventListener('click', function () {
            fecharMenu();
        });
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 900) {
            fecharMenu();
        }
    });

});