document.addEventListener('DOMContentLoaded', () => {

    const dados = window.MathPlayMemoria;

    if (!dados || !dados.perguntas || dados.perguntas.length === 0) {
        console.error('Dados do Memória Matemática não encontrados.');
        return;
    }

    const tabuleiro = document.getElementById('tabuleiro');
    const paresEncontradosEl = document.getElementById('paresEncontrados');
    const pontuacaoEl = document.getElementById('pontuacao');
    const acertosEl = document.getElementById('acertos');
    const errosEl = document.getElementById('erros');
    const progressoTexto = document.getElementById('progressoTexto');
    const barraProgresso = document.getElementById('barraProgresso');
    const dificuldadeEl = document.getElementById('dificuldadeAtual');
    const feedbackEl = document.getElementById('feedback');
    const reiniciarBtn = document.getElementById('reiniciarJogo');

    let cartas = [];
    let primeiraCarta = null;
    let segundaCarta = null;

    let bloqueado = false;

    let paresEncontrados = 0;
    let acertos = 0;
    let erros = 0;
    let pontuacao = 0;

    let partidaFinalizada = false;


    /* =========================
       EMBARALHAR
    ========================= */

    function embaralhar(array) {

        const copia = [...array];

        for (let i = copia.length - 1; i > 0; i--) {

            const j = Math.floor(Math.random() * (i + 1));

            [copia[i], copia[j]] = [copia[j], copia[i]];
        }

        return copia;
    }


    /* =========================
       CRIAR CARTAS
    ========================= */

    function criarCartas() {

        const cartasGeradas = [];

        dados.perguntas.forEach((pergunta) => {

            cartasGeradas.push({
                perguntaId: pergunta.id,
                tipo: 'operacao',
                valor: pergunta.enunciado,
                par: pergunta.resposta_correta || null
            });

            /*
             * O resultado correto não vem do PHP por segurança.
             *
             * Para o funcionamento visual do jogo, o resultado é
             * calculado somente para as operações matemáticas simples.
             */
            const resultado = calcularResultado(pergunta.enunciado);

            cartasGeradas.push({
                perguntaId: pergunta.id,
                tipo: 'resultado',
                valor: resultado !== null ? String(resultado) : '?',
                par: pergunta.enunciado
            });

        });

        return embaralhar(cartasGeradas);
    }


    /* =========================
       CALCULAR RESULTADO
    ========================= */

    function calcularResultado(enunciado) {

        let expressao = enunciado
            .replace(/\?/g, '')
            .replace(/=/g, '')
            .trim();

        expressao = expressao.replace(/x/gi, '*');
        expressao = expressao.replace(/÷/g, '/');

        /*
         * Aceitamos somente operações matemáticas simples.
         */

        if (!/^[0-9+\-*/().\s]+$/.test(expressao)) {
            return null;
        }

        try {

            const resultado = Function(
                `"use strict"; return (${expressao})`
            )();

            if (!Number.isFinite(resultado)) {
                return null;
            }

            return resultado;

        } catch (erro) {

            return null;
        }
    }


    /* =========================
       CRIAR TABULEIRO
    ========================= */

    function renderizarTabuleiro() {

        tabuleiro.innerHTML = '';

        cartas = criarCartas();

        cartas.forEach((carta, indice) => {

            const elemento = document.createElement('button');

            elemento.type = 'button';

            elemento.className = `carta ${carta.tipo}`;

            elemento.dataset.indice = indice;

            elemento.innerHTML = `

                <div class="carta-conteudo">

                    <div class="carta-frente">
                        ?
                    </div>

                    <div class="carta-verso">
                        ${escapeHtml(carta.valor)}
                    </div>

                </div>

            `;

            elemento.addEventListener(
                'click',
                () => selecionarCarta(elemento, indice)
            );

            tabuleiro.appendChild(elemento);

        });

    }


    /* =========================
       SELECIONAR CARTA
    ========================= */

    function selecionarCarta(elemento, indice) {

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

        if (!primeiraCarta) {

            primeiraCarta = {
                elemento: elemento,
                indice: indice,
                dados: cartas[indice]
            };

            return;
        }

        segundaCarta = {
            elemento: elemento,
            indice: indice,
            dados: cartas[indice]
        };

        verificarPar();
    }


    /* =========================
       VERIFICAR PAR
    ========================= */

    function verificarPar() {

        bloqueado = true;

        const primeira = primeiraCarta.dados;
        const segunda = segundaCarta.dados;

        const mesmoId =
            primeira.perguntaId === segunda.perguntaId;

        const tiposDiferentes =
            primeira.tipo !== segunda.tipo;

        if (mesmoId && tiposDiferentes) {

            acertos++;

            paresEncontrados++;

            const pergunta = dados.perguntas.find(
                item => Number(item.id) === Number(primeira.perguntaId)
            );

            if (pergunta) {
                pontuacao += Number(pergunta.pontuacao) || 10;
            }

            primeiraCarta.elemento.classList.add('encontrada');
            segundaCarta.elemento.classList.add('encontrada');

            atualizarStatus();

            mostrarFeedback(
                'sucesso',
                '🎉 Par correto! Muito bem!'
            );

            resetarSelecao();

            if (paresEncontrados === dados.perguntas.length) {

                finalizarVisualmente();

            } else {

                bloqueado = false;
            }

            return;
        }

        erros++;

        atualizarStatus();

        mostrarFeedback(
            'erro',
            '🤔 Esse não é o par. Tente memorizar as cartas!'
        );

        setTimeout(() => {

            primeiraCarta.elemento.classList.remove('virada');
            segundaCarta.elemento.classList.remove('virada');

            resetarSelecao();

            bloqueado = false;

        }, 1000);
    }


    /* =========================
       RESETAR SELEÇÃO
    ========================= */

    function resetarSelecao() {

        primeiraCarta = null;
        segundaCarta = null;
    }


    /* =========================
       STATUS
    ========================= */

    function atualizarStatus() {

        paresEncontradosEl.textContent = paresEncontrados;
        pontuacaoEl.textContent = pontuacao;
        acertosEl.textContent = acertos;
        errosEl.textContent = erros;

        const totalPares = dados.perguntas.length;

        const percentual = totalPares > 0
            ? Math.round((paresEncontrados / totalPares) * 100)
            : 0;

        progressoTexto.textContent = `${percentual}%`;

        barraProgresso.style.width = `${percentual}%`;

        atualizarDificuldade();
    }


    /* =========================
       DIFICULDADE
    ========================= */

    function atualizarDificuldade() {

        const total = dados.perguntas.length;

        if (paresEncontrados >= Math.ceil(total * 0.7)) {

            dificuldadeEl.textContent = 'Difícil';

        } else if (paresEncontrados >= Math.ceil(total * 0.3)) {

            dificuldadeEl.textContent = 'Médio';

        } else {

            dificuldadeEl.textContent = 'Fácil';
        }
    }


    /* =========================
       FEEDBACK
    ========================= */

    function mostrarFeedback(tipo, mensagem) {

        feedbackEl.className = `feedback ${tipo}`;

        feedbackEl.textContent = mensagem;

        feedbackEl.classList.remove('hidden');
    }


    /* =========================
       FINALIZAÇÃO
    ========================= */

    function finalizarVisualmente() {

        partidaFinalizada = true;

        mostrarFeedback(
            'final',
            `🏆 Parabéns! Você encontrou todos os pares e fez ${pontuacao} pontos.`
        );

        dificuldadeEl.textContent = 'Concluído';

        /*
         * Salva a partida no servidor.
         */

        salvarPartida();

    }


    /* =========================
       SALVAR PARTIDA
    ========================= */

    async function salvarPartida() {

        try {

            const resposta = await fetch(
                '../../api/memoria-matematica.php',
                {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json'
                    },

                    body: JSON.stringify({

                        jogo_id: dados.jogoId,
                        acertos: acertos,
                        erros: erros,
                        pontuacao: pontuacao

                    })
                }
            );

            const resultado = await resposta.json();

            if (!resultado.sucesso) {

                console.error(
                    resultado.mensagem || 'Erro ao salvar partida.'
                );

                return;
            }

            mostrarFeedback(
                'final',
                `🏆 Jogo concluído! Você fez ${pontuacao} pontos e ganhou ${resultado.xp_ganho || 0} XP.`
            );

            setTimeout(() => {

                window.location.href =
                    '../../aluno/progresso.php';

            }, 3000);

        } catch (erro) {

            console.error(
                'Erro ao salvar a partida:',
                erro
            );

            mostrarFeedback(
                'erro',
                'A partida terminou, mas houve um problema ao salvar seus dados.'
            );
        }
    }


    /* =========================
       REINICIAR
    ========================= */

    reiniciarBtn.addEventListener(
        'click',
        () => {

            primeiraCarta = null;
            segundaCarta = null;

            bloqueado = false;

            paresEncontrados = 0;
            acertos = 0;
            erros = 0;
            pontuacao = 0;

            partidaFinalizada = false;

            feedbackEl.classList.add('hidden');

            dificuldadeEl.textContent = 'Fácil';

            atualizarStatus();

            renderizarTabuleiro();

        }
    );


    /* =========================
       SEGURANÇA HTML
    ========================= */

    function escapeHtml(valor) {

        const div = document.createElement('div');

        div.textContent = valor;

        return div.innerHTML;
    }


    /* =========================
       INICIAR
    ========================= */

    atualizarStatus();

    renderizarTabuleiro();

});