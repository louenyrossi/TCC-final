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

            /*
             * CARTA DA EXPRESSÃO
             */

            cartasGeradas.push({
                perguntaId: pergunta.id,
                tipo: 'operacao',
                valor: pergunta.enunciado,
                par: pergunta.resposta_correta || null
            });


            /*
             * CARTA DO RESULTADO
             */

            const resultado = calcularResultado(pergunta.enunciado);

            cartasGeradas.push({
                perguntaId: pergunta.id,
                tipo: 'resultado',

                /*
                 * Primeiro tenta calcular o resultado.
                 *
                 * Se não conseguir, usa a resposta correta
                 * que veio do banco de dados.
                 *
                 * Assim nunca aparece "?" como resposta.
                 */

                valor: resultado !== null
                    ? String(resultado)
                    : String(pergunta.resposta_correta || 'Erro'),

                par: pergunta.enunciado
            });

        });

        return embaralhar(cartasGeradas);
    }


    /* =========================
       CALCULAR RESULTADO
    ========================= */

    function calcularResultado(enunciado) {

        try {

            let expressao = String(enunciado)
                .replace(/Calcule:\s*/gi, '')
                .replace(/=/g, '')
                .trim();


            /*
             * ÂNGULOS
             */

            const angulo = expressao.match(/(\d+(?:[.,]\d+)?)\s*°/);

        if (angulo) {

            const valor = Number(
                angulo[1].replace(',', '.')
            );

            if (valor === 90) {
                return 'Reto';
            }

            if (valor > 90 && valor < 180) {
                return 'Obtuso';
            }

            if (valor > 0 && valor < 90) {
                return 'Agudo';
            }

            if (valor === 180) {
                return 'Raso';
            }
        }

        /* =========================
           PORCENTAGEM
           Exemplo:
           10% de 50 → 5
        ========================= */

        const porcentagem = expressao.match(
            /(\d+(?:[.,]\d+)?)\s*%\s*de\s*(\d+(?:[.,]\d+)?)/i
        );

        if (porcentagem) {

            const percentual = Number(
                porcentagem[1].replace(',', '.')
            );

            const valor = Number(
                porcentagem[2].replace(',', '.')
            );

            return (percentual / 100) * valor;
        }

        /* =========================
           OPERAÇÕES
        ========================= */

        expressao = expressao
            .replace(/×/g, '*')
            .replace(/x/gi, '*')
            .replace(/÷/g, '/');

        /* =========================
           POTÊNCIAS
        ========================= */

        expressao = expressao
            .replace(/²/g, '**2')
            .replace(/³/g, '**3')
            .replace(/⁴/g, '**4')
            .replace(/⁵/g, '**5');

        /* =========================
           VÍRGULA DECIMAL
           7,5 → 7.5
        ========================= */

        expressao = expressao.replace(
            /(\d),(\d)/g,
            '$1.$2'
        );

        /* =========================
           FRAÇÕES E OPERAÇÕES
        ========================= */

        expressao = expressao.replace(/\s+/g, '');

        /* =========================
           SEGURANÇA
        ========================= */

        if (!/^[0-9+\-*/().]+$/.test(expressao)) {
            return null;
        }

        /* =========================
           CALCULAR
        ========================= */

        const resultado = Function(
            `"use strict"; return (${expressao})`
        )();

        if (!Number.isFinite(resultado)) {
            return null;
        }

        /* =========================
           FORMATAÇÃO
        ========================= */

        if (Number.isInteger(resultado)) {
            return resultado;
        }

        return Number(
            resultado.toFixed(2)
        );

    } catch (erro) {

        console.error(
            'Erro ao calcular expressão:',
            enunciado,
            erro
        );

        return null;
        }
    }


    /* =========================
       FORMATAR EXPRESSÃO
    ========================= */

    function formatarExpressao(expressao) {

        if (!expressao) {
            return '';
        }

        return String(expressao)
            .replace(/Calcule:\s*/gi, '')
            .replace(/\*\*2/g, '²')
            .replace(/\*\*3/g, '³')
            .replace(/\*\*4/g, '⁴')
            .replace(/\*\*5/g, '⁵')
            .replace(/\^2/g, '²')
            .replace(/\^3/g, '³')
            .replace(/\^4/g, '⁴')
            .replace(/\^5/g, '⁵')
            .replace(/\*/g, '×')
            .replace(/\//g, '÷')
            .trim();
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


            /*
             * A FRENTE mostra apenas o símbolo do jogo.
             *
             * Quando clicar, a carta vira e mostra
             * o conteúdo que está em carta.valor.
             */

            let valorCarta = carta.valor;

            if (carta.tipo === 'operacao') {
                valorCarta = formatarExpressao(valorCarta);
            }


            elemento.innerHTML = `

                <div class="carta-conteudo">

                    <div class="carta-frente">
                        🧠
                    </div>

                    <div class="carta-verso">
                        ${escapeHtml(valorCarta)}
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


        /*
         * VIRA A CARTA
         */

        elemento.classList.add('virada');


        /*
         * PRIMEIRA CARTA
         */

        if (!primeiraCarta) {

            primeiraCarta = {
                elemento: elemento,
                indice: indice,
                dados: cartas[indice]
            };

            return;
        }


        /*
         * SEGUNDA CARTA
         */

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


        /*
         * O PAR PRECISA SER DA MESMA PERGUNTA
         */

        const mesmoId =
            Number(primeira.perguntaId) === Number(segunda.perguntaId);


        /*
         * Uma carta precisa ser operação
         * e a outra precisa ser resultado.
         */

        const tiposDiferentes =
            primeira.tipo !== segunda.tipo;


        /*
         * PAR CORRETO
         */

        if (mesmoId && tiposDiferentes) {

            acertos++;

            paresEncontrados++;


            const pergunta = dados.perguntas.find(
                item =>
                    Number(item.id) === Number(primeira.perguntaId)
            );


            if (pergunta) {

                pontuacao +=
                    Number(pergunta.pontuacao) || 10;

            }


            /*
             * Marca as cartas como encontradas
             */

            primeiraCarta.elemento.classList.add('encontrada');

            segundaCarta.elemento.classList.add('encontrada');


            atualizarStatus();


            mostrarFeedback(
                'sucesso',
                '🎉 Par correto! Muito bem!'
            );


            resetarSelecao();


            /*
             * VERIFICA SE TERMINOU
             */

            if (
                paresEncontrados ===
                dados.perguntas.length
            ) {

                finalizarVisualmente();

            } else {

                bloqueado = false;

            }

            return;
        }


        /*
         * PAR ERRADO
         */

        erros++;

        atualizarStatus();


        mostrarFeedback(
            'erro',
            '🤔 Esse não é o par. Tente memorizar as cartas!'
        );


        /*
         * Depois de 1 segundo,
         * as cartas voltam a fechar.
         */

        setTimeout(() => {

            primeiraCarta.elemento
                .classList
                .remove('virada');

            segundaCarta.elemento
                .classList
                .remove('virada');


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

        paresEncontradosEl.textContent =
            paresEncontrados;

        pontuacaoEl.textContent =
            pontuacao;

        acertosEl.textContent =
            acertos;

        errosEl.textContent =
            erros;


        const totalPares =
            dados.perguntas.length;


        const percentual =
            totalPares > 0
                ? Math.round(
                    (paresEncontrados / totalPares) * 100
                )
                : 0;


        progressoTexto.textContent =
            `${percentual}%`;


        barraProgresso.style.width =
            `${percentual}%`;


        atualizarDificuldade();
    }


    /* =========================
       DIFICULDADE
    ========================= */

    function atualizarDificuldade() {

        const total =
            dados.perguntas.length;


        if (
            paresEncontrados >=
            Math.ceil(total * 0.7)
        ) {

            dificuldadeEl.textContent =
                'Difícil';

        } else if (
            paresEncontrados >=
            Math.ceil(total * 0.3)
        ) {

            dificuldadeEl.textContent =
                'Médio';

        } else {

            dificuldadeEl.textContent =
                'Fácil';
        }
    }


    /* =========================
       FEEDBACK
    ========================= */

    function mostrarFeedback(tipo, mensagem) {

        feedbackEl.className =
            `feedback ${tipo}`;

        feedbackEl.textContent =
            mensagem;

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


        dificuldadeEl.textContent =
            'Concluído';


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


            const resultado =
                await resposta.json();


            if (!resultado.sucesso) {

                console.error(
                    resultado.mensagem ||
                    'Erro ao salvar partida.'
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


            dificuldadeEl.textContent =
                'Fácil';


            atualizarStatus();

            renderizarTabuleiro();

        }
    );


    /* =========================
       SEGURANÇA HTML
    ========================= */

    function escapeHtml(valor) {

        const div =
            document.createElement('div');

        div.textContent =
            valor;

        return div.innerHTML;
    }


    /* =========================
       INICIAR JOGO
    ========================= */

    atualizarStatus();

    renderizarTabuleiro();

});