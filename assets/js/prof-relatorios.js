document.addEventListener('DOMContentLoaded', function () {

    const sidebar = document.getElementById('sidebar');
    const menuButton = document.getElementById('menuButton');
    const overlay = document.getElementById('sidebarOverlay');
    const printButton = document.getElementById('printButton');

    /*
    |--------------------------------------------------------------------------
    | MENU MOBILE
    |--------------------------------------------------------------------------
    */

    if (sidebar && menuButton && overlay) {

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

        document.querySelectorAll('.nav-item, .logout-link')
            .forEach(function (link) {

                link.addEventListener('click', function () {
                    fecharMenu();
                });

            });

        window.addEventListener('resize', function () {

            if (window.innerWidth >= 900) {
                fecharMenu();
            }

        });
    }

    /*
    |--------------------------------------------------------------------------
    | IMPRESSÃO
    |--------------------------------------------------------------------------
    */

    if (printButton) {

        printButton.addEventListener('click', function () {
            window.print();
        });

    }

});