document.addEventListener('DOMContentLoaded', () => {

    const dados = window.MathPlayMemoria;

    if (!dados || !Array.isArray(dados.cartas)) {
        console.error('Dados do jogo não encontrados.');
        return;
    }

    const tabuleiro = document.getElementById('tabuleiro');
    const acertosElemento = document.getElementById('acertos');
    const errosElemento = document.getElementById('erros');
    const pontuacaoElemento = document.getElementById('pontuacao');
    const dificuldadeElemento = document.getElementById('dificuldade');
    const progressoTexto = document.getElementById('progresso-texto');
    const barraProgresso = document.getElementById('barra-progresso');
    const feedback = document.getElementById('feedback');

    const botaoDica = document.getElementById('botao-dica');
    const botaoReiniciar = document.getElementById('botao-reiniciar');
    const dica = document.getElementById('dica');

    let primeiraCarta = null;
    let segundaCarta = null;

    let bloqueado = false;
    let partidaFinalizada = false;

    let acertos = 0;
    let erros = 0;
    let pontuacao = 0;

    const totalPares = dados.cartas.length / 2;

    /*
    |--------------------------------------------------------------------------
    | Criação das cartas
    |--------------------------------------------------------------------------
    */

    function criarTabuleiro() {

        tabuleiro.innerHTML = '';

        dados.cartas.forEach((carta) => {

            const elemento = document.createElement('button');

            elemento.type = 'button';
            elemento.className = 'carta';

            elemento.dataset.token = carta.token;

            elemento.setAttribute(
                'aria-label',
                'Carta fechada'
            );

            elemento.innerHTML = `
                <span class="carta-conteudo">

                    <span class="carta-frente">
                        ?
                    </span>

                    <span class="carta-verso">
                        ${escapeHtml(carta.valor)}
                    </span>

                </span>
            `;

            elemento.addEventListener(
                'click',
                () => selecionarCarta(elemento, carta)
            );

            tabuleiro.appendChild(elemento);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Seleciona uma carta
    |--------------------------------------------------------------------------
    */

    function selecionarCarta(elemento, carta) {

        if (bloqueado || partidaFinalizada) {
            return;
        }

        if (elemento.classList.contains('virada')) {
            return;
        }

        if (elemento.classList.contains('encontrada')) {
            return;
        }

        elemento.classList.add('virada');

        elemento.setAttribute(
            'aria-label',
            carta.tipo === 'operacao'
                ? `Operação: ${carta.valor}`
                : `Resultado: ${carta.valor}`
        );

        if (!primeiraCarta) {

            primeiraCarta = {
                elemento: elemento,
                dados: carta
            };

            mostrarFeedback(
                'Escolha mais uma carta.',
                ''
            );

            return;
        }

        segundaCarta = {
            elemento: elemento,
            dados: carta
        };

        bloqueado = true;

        verificarPar();
    }


    /*
    |--------------------------------------------------------------------------
    | Verifica o par
    |--------------------------------------------------------------------------
    */

    async function verificarPar() {

        if (!primeiraCarta || !segundaCarta) {
            return;
        }

        const token1 = primeiraCarta.dados.token;
        const token2 = segundaCarta.dados.token;

        try {

            const resposta = await fetch(
                '../../api/memoria-matematica.php',
                {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json'
                    },

                    body: JSON.stringify({
                        acao: 'tentativa',
                        jogo_id: dados.jogoId,
                        carta1: token1,
                        carta2: token2
                    })
                }
            );

            const resultado = await resposta.json();

            if (!resposta.ok || !resultado.sucesso) {

                throw new Error(
                    resultado.mensagem ||
                    'Não foi possível validar a jogada.'
                );
            }

            atualizarStatus(resultado);

            if (resultado.correta) {

                primeiraCarta.elemento.classList.add(
                    'encontrada'
                );

                segundaCarta.elemento.classList.add(
                    'encontrada'
                );

                mostrarFeedback(
                    resultado.mensagem ||
                    '🎉 Par correto!',
                    'sucesso'
                );

                primeiraCarta.elemento.disabled = true;
                segundaCarta.elemento.disabled = true;

                primeiraCarta = null;
                segundaCarta = null;

                bloqueado = false;

                if (resultado.partida_finalizada) {
                    finalizarJogo(resultado);
                }

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Erro
            |--------------------------------------------------------------------------
            */

            mostrarFeedback(
                resultado.mensagem ||
                'Quase! Essas cartas não formam um par.',
                'erro'
            );

            setTimeout(() => {

                if (primeiraCarta) {
                    primeiraCarta.elemento.classList.remove(
                        'virada'
                    );

                    primeiraCarta.elemento.setAttribute(
                        'aria-label',
                        'Carta fechada'
                    );
                }

                if (segundaCarta) {
                    segundaCarta.elemento.classList.remove(
                        'virada'
                    );

                    segundaCarta.elemento.setAttribute(
                        'aria-label',
                        'Carta fechada'
                    );
                }

                primeiraCarta = null;
                segundaCarta = null;

                bloqueado = false;

            }, 1000);

        } catch (erro) {

            console.error(erro);

            mostrarFeedback(
                erro.message ||
                'Ocorreu um erro ao validar a jogada.',
                'erro'
            );

            if (primeiraCarta) {
                primeiraCarta.elemento.classList.remove(
                    'virada'
                );
            }

            if (segundaCarta) {
                segundaCarta.elemento.classList.remove(
                    'virada'
                );
            }

            primeiraCarta = null;
            segundaCarta = null;

            bloqueado = false;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Atualiza os números do jogo
    |--------------------------------------------------------------------------
    */

    function atualizarStatus(resultado) {

        if (typeof resultado.acertos !== 'undefined') {
            acertos = Number(resultado.acertos);
        }

        if (typeof resultado.erros !== 'undefined') {
            erros = Number(resultado.erros);
        }

        if (typeof resultado.pontuacao !== 'undefined') {
            pontuacao = Number(resultado.pontuacao);
        }

        acertosElemento.textContent = acertos;
        errosElemento.textContent = erros;
        pontuacaoElemento.textContent = pontuacao;

        atualizarProgresso();
        atualizarDificuldade();
    }


    /*
    |--------------------------------------------------------------------------
    | Atualiza progresso visual
    |--------------------------------------------------------------------------
    */

    function atualizarProgresso() {

        if (totalPares <= 0) {
            return;
        }

        const porcentagem = Math.round(
            (acertos / totalPares) * 100
        );

        const progresso = Math.min(
            100,
            Math.max(0, porcentagem)
        );

        progressoTexto.textContent = `${progresso}%`;

        barraProgresso.style.width = `${progresso}%`;
    }


    /*
    |--------------------------------------------------------------------------
    | Dificuldade visual
    |--------------------------------------------------------------------------
    */

    function atualizarDificuldade() {

        if (acertos <= 2) {

            dificuldadeElemento.textContent = 'Fácil';

        } else if (acertos <= 5) {

            dificuldadeElemento.textContent = 'Médio';

        } else {

            dificuldadeElemento.textContent = 'Difícil';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Feedback
    |--------------------------------------------------------------------------
    */

    function mostrarFeedback(mensagem, tipo) {

        feedback.textContent = mensagem;

        feedback.classList.remove(
            'sucesso',
            'erro'
        );

        if (tipo) {
            feedback.classList.add(tipo);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Finalização
    |--------------------------------------------------------------------------
    */

    function finalizarJogo(resultado) {

        partidaFinalizada = true;
        bloqueado = true;

        atualizarStatus(resultado);

        mostrarFeedback(
            `🏆 Parabéns! Você terminou com ${resultado.acertos} acertos e ${resultado.pontuacao} pontos!`,
            'sucesso'
        );

        setTimeout(() => {

            window.location.href =
                '../../aluno/progresso.php';

        }, 3000);
    }


    /*
    |--------------------------------------------------------------------------
    | Dica
    |--------------------------------------------------------------------------
    */

    botaoDica.addEventListener(
        'click',
        () => {

            dica.hidden = !dica.hidden;

            if (dica.hidden) {

                botaoDica.textContent = '💡 Dica';

            } else {

                botaoDica.textContent = '💡 Ocultar dica';
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Reiniciar
    |--------------------------------------------------------------------------
    */

    botaoReiniciar.addEventListener(
        'click',
        async () => {

            if (
                !confirm(
                    'Deseja reiniciar o jogo? O progresso desta partida será perdido.'
                )
            ) {
                return;
            }

            try {

                const resposta = await fetch(
                    '../../api/memoria-matematica.php',
                    {
                        method: 'POST',

                        headers: {
                            'Content-Type': 'application/json'
                        },

                        body: JSON.stringify({
                            acao: 'reiniciar',
                            jogo_id: dados.jogoId
                        })
                    }
                );

                const resultado = await resposta.json();

                if (!resposta.ok || !resultado.sucesso) {

                    throw new Error(
                        resultado.mensagem ||
                        'Não foi possível reiniciar.'
                    );
                }

                window.location.reload();

            } catch (erro) {

                console.error(erro);

                mostrarFeedback(
                    erro.message ||
                    'Não foi possível reiniciar o jogo.',
                    'erro'
                );
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Segurança para exibição dos valores
    |--------------------------------------------------------------------------
    */

    function escapeHtml(valor) {

        const div = document.createElement('div');

        div.textContent = String(valor);

        return div.innerHTML;
    }


    /*
    |--------------------------------------------------------------------------
    | Inicialização
    |--------------------------------------------------------------------------
    */

    criarTabuleiro();
    atualizarProgresso();
    atualizarDificuldade();

});