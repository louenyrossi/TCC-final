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

            const enunciado = String(pergunta.enunciado || '').trim();

            const resultado = calcularResultado(enunciado);

            cartasGeradas.push({
                perguntaId: Number(pergunta.id),
                tipo: 'operacao',
                valor: formatarExpressao(enunciado)
            });

            cartasGeradas.push({
                perguntaId: Number(pergunta.id),
                tipo: 'resultado',
                valor: resultado !== null
                    ? formatarNumero(resultado)
                    : 'Erro'
            });

        });

        return embaralhar(cartasGeradas);
    }


    /* =========================
       CALCULAR RESULTADO
    ========================= */

    function calcularResultado(enunciado) {

        if (!enunciado) {
            return null;
        }

        try {

            let expressao = String(enunciado)
                .trim()
                .replace(/,/g, '.')
                .replace(/×/g, '*')
                .replace(/x/gi, '*')
                .replace(/÷/g, '/');

            /*
             * Remove espaços desnecessários.
             */
            expressao = expressao.replace(/\s+/g, '');


            /*
             * =========================
             * RAIZ QUADRADA
             * =========================
             *
             * Exemplo:
             * √49 + 3
             *
             * vira:
             * sqrt(49) + 3
             */

            expressao = expressao.replace(
                /√(\d+(?:\.\d+)?)/g,
                'Math.sqrt($1)'
            );


            /*
             * =========================
             * POTÊNCIAS
             * =========================
             *
             * 3² -> 3**2
             * 2³ -> 2**3
             * 4⁴ -> 4**4
             */

            expressao = expressao
                .replace(/⁰/g, '0')
                .replace(/¹/g, '1')
                .replace(/²/g, '2')
                .replace(/³/g, '3')
                .replace(/⁴/g, '4')
                .replace(/⁵/g, '5')
                .replace(/⁶/g, '6')
                .replace(/⁷/g, '7')
                .replace(/⁸/g, '8')
                .replace(/⁹/g, '9');


            /*
             * Converte:
             *
             * 3²
             * 5²
             * 2³
             *
             * para:
             *
             * 3**2
             * 5**2
             * 2**3
             */

            expressao = expressao.replace(
                /(\d+(?:\.\d+)?)\s*([0-9]+)/g,
                (match, base, expoente) => {
                    return `${base}**${expoente}`;
                }
            );


            /*
             * Verifica se ainda existe algum
             * caractere que não pertence à expressão.
             */

            if (
                !/^[0-9+\-*/().\s]+$/.test(
                    expressao.replace(/Math\.sqrt/g, '')
                )
            ) {
                return null;
            }


            /*
             * Avalia a expressão matemática.
             */

            const resultado = Function(
                `"use strict"; return (${expressao})`
            )();


            if (!Number.isFinite(resultado)) {
                return null;
            }


            return resultado;

        } catch (erro) {

            console.error(
                'Erro ao calcular:',
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

        let texto = String(expressao || '').trim();

        /*
         * Remove "Calcule:" caso ainda exista
         * em alguma pergunta antiga do banco.
         */

        texto = texto.replace(/^calcule\s*:\s*/i, '');

        /*
         * Converte ** para potência visual.
         *
         * Exemplo:
         * 3**2 -> 3²
         */

        texto = texto.replace(
            /(\d+)\*\*(\d+)/g,
            (match, base, expoente) => {
                return `${base}${converterExpoente(expoente)}`;
            }
        );

        /*
         * Converte ^ para potência visual.
         *
         * Exemplo:
         * 2^3 -> 2³
         */

        texto = texto.replace(
            /(\d+)\^(\d+)/g,
            (match, base, expoente) => {
                return `${base}${converterExpoente(expoente)}`;
            }
        );

        /*
         * Padroniza multiplicação e divisão.
         */

        texto = texto
            .replace(/\*/g, ' × ')
            .replace(/\//g, ' ÷ ');

        /*
         * Mantém a raiz quadrada.
         */

        texto = texto.replace(/\s+/g, ' ').trim();

        return escapeHtml(texto);
    }


    /* =========================
       CONVERTER EXPOENTE
    ========================= */

    function converterExpoente(numero) {

        const mapa = {
            '0': '⁰',
            '1': '¹',
            '2': '²',
            '3': '³',
            '4': '⁴',
            '5': '⁵',
            '6': '⁶',
            '7': '⁷',
            '8': '⁸',
            '9': '⁹'
        };

        return String(numero)
            .split('')
            .map(numero => mapa[numero] || numero)
            .join('');
    }


    /* =========================
       FORMATAR NÚMERO
    ========================= */

    function formatarNumero(numero) {

        if (!Number.isFinite(numero)) {
            return 'Erro';
        }

        /*
         * Se for inteiro:
         *
         * 24 -> 24
         */

        if (Number.isInteger(numero)) {
            return String(numero);
        }

        /*
         * Se houver decimal:
         *
         * 10.5 -> 10,5
         */

        return String(
            Number(numero.toFixed(2))
        ).replace('.', ',');
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
                        ${carta.valor}
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

        /*
         * O par é identificado pelo ID da pergunta.
         *
         * Isso permite ter, por exemplo:
         *
         * 7 + 5 = 12
         * 4 × 3 = 12
         *
         * sem o jogo confundir os dois.
         */

        const mesmoId =
            primeira.perguntaId === segunda.perguntaId;

        const tiposDiferentes =
            primeira.tipo !== segunda.tipo;

        if (mesmoId && tiposDiferentes) {

            acertos++;

            paresEncontrados++;

            const pergunta = dados.perguntas.find(
                item =>
                    Number(item.id) ===
                    Number(primeira.perguntaId)
            );

            if (pergunta) {
                pontuacao +=
                    Number(pergunta.pontuacao) || 10;
            }

            primeiraCarta.elemento.classList.add(
                'encontrada'
            );

            segundaCarta.elemento.classList.add(
                'encontrada'
            );

            atualizarStatus();

            mostrarFeedback(
                'sucesso',
                '🎉 Par correto! Muito bem!'
            );

            resetarSelecao();

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

        erros++;

        atualizarStatus();

        mostrarFeedback(
            'erro',
            '🤔 Esse não é o par. Tente memorizar as cartas!'
        );

        setTimeout(() => {

            primeiraCarta.elemento.classList.remove(
                'virada'
            );

            segundaCarta.elemento.classList.remove(
                'virada'
            );

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

    function mostrarFeedback(
        tipo,
        mensagem
    ) {

        feedbackEl.className =
            `feedback ${tipo}`;

        feedbackEl.textContent =
            mensagem;

        feedbackEl.classList.remove(
            'hidden'
        );
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

        salvarPartida();
    }


    /* =========================
       SALVAR PARTIDA
    ========================= */

    async function salvarPartida() {

        try {

            const resposta =
                await fetch(
                    '../../api/memoria-matematica.php',
                    {
                        method: 'POST',

                        headers: {
                            'Content-Type':
                                'application/json'
                        },

                        body: JSON.stringify({

                            jogo_id:
                                dados.jogoId,

                            acertos:
                                acertos,

                            erros:
                                erros,

                            pontuacao:
                                pontuacao
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

            feedbackEl.classList.add(
                'hidden'
            );

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
       INICIAR
    ========================= */

    atualizarStatus();

    renderizarTabuleiro();

});