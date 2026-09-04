document.addEventListener('DOMContentLoaded', () => {

    const form = document.getElementById('cadastroForm');

    const nome = document.getElementById('nome');
    const email = document.getElementById('email');

    const senha = document.getElementById('senha');
    const confirmarSenha = document.getElementById('confirmar_senha');

    const mostrarSenha = document.getElementById('mostrarSenha');
    const mostrarConfirmarSenha = document.getElementById('mostrarConfirmarSenha');

    const forcaProgresso = document.getElementById('forcaProgresso');
    const forcaTexto = document.getElementById('forcaTexto');

    const erroNome = document.getElementById('erroNome');
    const erroEmail = document.getElementById('erroEmail');
    const erroSenha = document.getElementById('erroSenha');
    const erroConfirmarSenha = document.getElementById('erroConfirmarSenha');


    /*
     * MOSTRAR / OCULTAR SENHA
     */

    function alternarSenha(input, botao) {

        if (input.type === 'password') {

            input.type = 'text';
            botao.textContent = '🙈';

        } else {

            input.type = 'password';
            botao.textContent = '👁';

        }

    }


    mostrarSenha.addEventListener('click', () => {
        alternarSenha(senha, mostrarSenha);
    });


    mostrarConfirmarSenha.addEventListener('click', () => {
        alternarSenha(confirmarSenha, mostrarConfirmarSenha);
    });


    /*
     * FORÇA DA SENHA
     */

    function verificarForcaSenha(valor) {

        if (valor.length === 0) {

            forcaProgresso.style.width = '0%';
            forcaTexto.textContent = 'Digite uma senha';

            return;
        }


        let pontos = 0;


        if (valor.length >= 6) {
            pontos++;
        }

        if (valor.length >= 10) {
            pontos++;
        }

        if (/[A-Z]/.test(valor)) {
            pontos++;
        }

        if (/[0-9]/.test(valor)) {
            pontos++;
        }

        if (/[^A-Za-z0-9]/.test(valor)) {
            pontos++;
        }


        if (pontos <= 1) {

            forcaProgresso.style.width = '25%';
            forcaTexto.textContent = 'Senha fraca';

        } else if (pontos <= 3) {

            forcaProgresso.style.width = '60%';
            forcaTexto.textContent = 'Senha média';

        } else {

            forcaProgresso.style.width = '100%';
            forcaTexto.textContent = 'Senha forte';

        }

    }


    senha.addEventListener('input', () => {
        verificarForcaSenha(senha.value);
    });


    /*
     * VALIDAR E-MAIL
     */

    function emailValido(valor) {

        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor);

    }


    /*
     * LIMPAR ERROS
     */

    function limparErros() {

        erroNome.textContent = '';
        erroEmail.textContent = '';
        erroSenha.textContent = '';
        erroConfirmarSenha.textContent = '';

    }


    /*
     * VALIDAÇÃO DO FORMULÁRIO
     */

    form.addEventListener('submit', (event) => {

        limparErros();

        let valido = true;


        if (nome.value.trim().length < 3) {

            erroNome.textContent =
                'Digite seu nome completo.';

            valido = false;

        }


        if (!emailValido(email.value.trim())) {

            erroEmail.textContent =
                'Digite um e-mail válido.';

            valido = false;

        }


        if (senha.value.length < 6) {

            erroSenha.textContent =
                'A senha deve possuir pelo menos 6 caracteres.';

            valido = false;

        }


        if (confirmarSenha.value !== senha.value) {

            erroConfirmarSenha.textContent =
                'As senhas não coincidem.';

            valido = false;

        }


        if (!valido) {

            event.preventDefault();

            return;

        }


        /*
         * Evita múltiplos cliques enquanto o formulário
         * está sendo enviado.
         */

        const botao = document.getElementById('btnCadastrar');

        botao.disabled = true;
        botao.querySelector('span:first-child').textContent =
            'Criando conta...';

    });

});