document.addEventListener('DOMContentLoaded', () => {

    const menuButton =
        document.getElementById('menuButton');

    const sidebar =
        document.getElementById('sidebar');

    const sidebarClose =
        document.getElementById('sidebarClose');

    const overlay =
        document.getElementById('sidebarOverlay');


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


    if (menuButton) {

        menuButton.addEventListener(
            'click',
            abrirMenu
        );

    }


    if (sidebarClose) {

        sidebarClose.addEventListener(
            'click',
            fecharMenu
        );

    }


    if (overlay) {

        overlay.addEventListener(
            'click',
            fecharMenu
        );

    }


    document
        .querySelectorAll('.nav-item')
        .forEach(link => {

            link.addEventListener('click', () => {

                if (window.innerWidth <= 900) {
                    fecharMenu();
                }

            });

        });


    window.addEventListener('resize', () => {

        if (window.innerWidth > 900) {
            fecharMenu();
        }

    });

});