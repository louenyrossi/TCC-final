document.addEventListener('DOMContentLoaded', function () {

    const menuButton = document.getElementById('menu-button');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    const searchInput = document.getElementById('search-aluno');
    const filterTurma = document.getElementById('filter-turma');

    const studentsList = document.getElementById('students-list');
    const noResults = document.getElementById('no-results');
    const resultsCount = document.getElementById('results-count');


    /* MENU MOBILE */

    function abrirMenu() {

        if (!sidebar || !overlay) {
            return;
        }

        sidebar.classList.add('open');
        overlay.classList.add('active');
        document.body.classList.add('menu-open');
    }


    function fecharMenu() {

        if (!sidebar || !overlay) {
            return;
        }

        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.classList.remove('menu-open');
    }


    if (menuButton) {
        menuButton.addEventListener('click', abrirMenu);
    }

    if (overlay) {
        overlay.addEventListener('click', fecharMenu);
    }


    document
        .querySelectorAll('.sidebar a')
        .forEach(function (link) {

            link.addEventListener('click', fecharMenu);

        });


    /* FILTRO DE ALUNOS */

    function filtrarAlunos() {

        if (!studentsList) {
            return;
        }

        const cards = studentsList.querySelectorAll('.student-card');

        const busca = searchInput
            ? searchInput.value.trim().toLowerCase()
            : '';

        const turmaSelecionada = filterTurma
            ? filterTurma.value
            : '';

        let encontrados = 0;


        cards.forEach(function (card) {

            const nome = card.dataset.name || '';
            const turma = card.dataset.turma || '';

            const correspondeNome =
                nome.includes(busca);

            const correspondeTurma =
                !turmaSelecionada ||
                turma === turmaSelecionada;

            const mostrar =
                correspondeNome &&
                correspondeTurma;


            if (mostrar) {

                card.style.display = 'flex';
                encontrados++;

            } else {

                card.style.display = 'none';

            }

        });


        if (resultsCount) {

            resultsCount.textContent =
                encontrados +
                ' aluno(s) encontrado(s)';

        }


        if (noResults) {

            noResults.hidden = encontrados !== 0;

        }

    }


    if (searchInput) {
        searchInput.addEventListener(
            'input',
            filtrarAlunos
        );
    }


    if (filterTurma) {
        filterTurma.addEventListener(
            'change',
            filtrarAlunos
        );
    }

});