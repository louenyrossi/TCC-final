document.addEventListener('DOMContentLoaded', () => {

    const form = document.getElementById('loginForm');

    const email = document.getElementById('email');
    const senha = document.getElementById('senha');

    const mostrarSenha = document.getElementById('mostrarSenha');

    const erroEmail = document.getElementById('erroEmail');
    const erroSenha = document.getElementById('erroSenha');

    const botaoEntrar = document.getElementById('btnEntrar');


    /*
     * MOSTRAR / OCULTAR SENHA
     */

    mostrarSenha.addEventListener('click', () => {

        if (senha.type === 'password') {

            senha.type = 'text';

            mostrarSenha.textContent = '🙈';

            mostrarSenha.setAttribute(
                'aria-label',
                'Ocultar senha'
            );

        } else {

            senha.type = 'password';

            mostrarSenha.textContent = '👁';

            mostrarSenha.setAttribute(
                'aria-label',
                'Mostrar senha'
            );

        }

    });


    /*
     * VALIDAÇÃO DO E-MAIL
     */

    function emailValido(valor) {

        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor);

    }


    /*
     * LIMPAR ERROS
     */

    function limparErros() {

        erroEmail.textContent = '';
        erroSenha.textContent = '';

    }


    /*
     * VALIDAÇÃO
     */

    form.addEventListener('submit', (event) => {

        limparErros();

        let valido = true;


        if (!emailValido(email.value.trim())) {

            erroEmail.textContent =
                'Digite um e-mail válido.';

            valido = false;

        }


        if (senha.value.length === 0) {

            erroSenha.textContent =
                'Digite sua senha.';

            valido = false;

        }


        if (!valido) {

            event.preventDefault();

            return;

        }


        /*
         * Evita vários cliques no botão.
         */

        botaoEntrar.disabled = true;

        botaoEntrar.querySelector(
            'span:first-child'
        ).textContent = 'Entrando...';

    });

});