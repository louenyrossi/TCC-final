document.addEventListener('DOMContentLoaded', function () {

    const menuButton = document.getElementById('menu-button');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if (!menuButton || !sidebar || !overlay) {
        return;
    }

    function abrirMenu() {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        document.body.classList.add('menu-open');
    }

    function fecharMenu() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.classList.remove('menu-open');
    }

    menuButton.addEventListener('click', abrirMenu);

    overlay.addEventListener('click', fecharMenu);

    document
        .querySelectorAll('.sidebar a')
        .forEach(function (link) {
            link.addEventListener('click', fecharMenu);
        });

});