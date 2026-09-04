document.addEventListener('DOMContentLoaded', () => {

    const perguntas = window.MathPlayCaixa.perguntas || [];

    let perguntaAtual = 0;
    let pontuacao = 0;
    let acertos = 0;
    let erros = 0;
    let dicaUsada = false;

    const questionElement = document.getElementById('question');
    const questionNumberElement = document.getElementById('question-number');
    const questionTotalElement = document.getElementById('question-total');

    const scoreElement = document.getElementById('score');
    const correctElement = document.getElementById('correct');
    const incorrectElement = document.getElementById('incorrect');

    const progressText = document.getElementById('progress-text');
    const progressFill = document.getElementById('progress-fill');

    const difficultyElement = document.getElementById('difficulty');

    const answerForm = document.getElementById('answer-form');
    const answerInput = document.getElementById('answer');
    const answerButton = document.getElementById('answer-button');

    const hintButton = document.getElementById('hint-button');
    const hintText = document.getElementById('hint-text');

    const feedback = document.getElementById('feedback');
    const feedbackIcon = document.getElementById('feedback-icon');
    const feedbackTitle = document.getElementById('feedback-title');
    const feedbackMessage = document.getElementById('feedback-message');

    const nextButton = document.getElementById('next-button');


    /*
    |--------------------------------------------------------------------------
    | Verificação inicial
    |--------------------------------------------------------------------------
    */

    if (perguntas.length === 0) {

        questionElement.textContent =
            'Não existem perguntas disponíveis.';

        answerForm.style.display = 'none';

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Carregar pergunta
    |--------------------------------------------------------------------------
    */

    function carregarPergunta() {

        const pergunta = perguntas[perguntaAtual];

        if (!pergunta) {
            finalizarJogo();
            return;
        }

        questionNumberElement.textContent =
            perguntaAtual + 1;

        questionTotalElement.textContent =
            `/ ${perguntas.length}`;

        questionElement.textContent =
            pergunta.enunciado;

        difficultyElement.textContent =
            formatarDificuldade(pergunta.dificuldade);

        answerInput.value = '';

        answerInput.disabled = false;
        answerButton.disabled = false;

        feedback.classList.add('hidden');
        nextButton.classList.add('hidden');

        hintText.classList.add('hidden');

        hintText.textContent = '';

        hintButton.style.display = 'inline-flex';

        dicaUsada = false;

        atualizarProgresso();

        answerInput.focus();
    }


    /*
    |--------------------------------------------------------------------------
    | Formatar dificuldade
    |--------------------------------------------------------------------------
    */

    function formatarDificuldade(dificuldade) {

        const dificuldades = {
            facil: 'Fácil',
            medio: 'Médio',
            dificil: 'Difícil'
        };

        return dificuldades[dificuldade] || dificuldade;
    }


    /*
    |--------------------------------------------------------------------------
    | Atualizar progresso
    |--------------------------------------------------------------------------
    */

    function atualizarProgresso() {

        const progresso =
            Math.round(
                (perguntaAtual / perguntas.length) * 100
            );

        progressText.textContent =
            `${progresso}%`;

        progressFill.style.width =
            `${progresso}%`;
    }


    /*
    |--------------------------------------------------------------------------
    | Dicas
    |--------------------------------------------------------------------------
    */

    hintButton.addEventListener('click', () => {

        const pergunta = perguntas[perguntaAtual];

        if (!pergunta || dicaUsada) {
            return;
        }

        dicaUsada = true;

        hintButton.style.display = 'none';

        hintText.textContent =
            gerarDica(pergunta.enunciado);

        hintText.classList.remove('hidden');
    });


    function gerarDica(enunciado) {

        const texto = enunciado.toLowerCase();

        if (
            texto.includes('troco') ||
            texto.includes('pagou')
        ) {

            return '💡 Para descobrir o troco, subtraia o valor da compra do valor que o cliente pagou.';
        }

        if (
            texto.includes('totalizou') ||
            texto.includes('valor total')
        ) {

            return '💡 Some os valores de todos os produtos para encontrar o total da compra.';
        }

        return '💡 Leia os valores com atenção e identifique qual operação matemática a situação está pedindo.';
    }


    /*
    |--------------------------------------------------------------------------
    | Enviar resposta
    |--------------------------------------------------------------------------
    */

    answerForm.addEventListener('submit', (event) => {

        event.preventDefault();

        const resposta = answerInput.value.trim();

        if (resposta === '') {

            answerInput.focus();

            return;
        }

        verificarResposta(resposta);
    });


    /*
    |--------------------------------------------------------------------------
    | Verificar resposta
    |--------------------------------------------------------------------------
    |
    | IMPORTANTE:
    | A resposta correta NÃO está disponível no JavaScript.
    |
    | O JavaScript envia a resposta para o PHP.
    | O servidor fará a validação usando o banco.
    |
    */

    async function verificarResposta(resposta) {

        const pergunta = perguntas[perguntaAtual];

        if (!pergunta) {
            return;
        }

        answerInput.disabled = true;
        answerButton.disabled = true;

        answerButton.textContent = 'Verificando...';

        try {

            const response = await fetch(
                '../../api/caixa-matematico.php',
                {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json'
                    },

                    body: JSON.stringify({

                        acao: 'responder',

                        usuario_id:
                            window.MathPlayCaixa.usuarioId,

                        jogo_id:
                            window.MathPlayCaixa.jogoId,

                        pergunta_id:
                            pergunta.id,

                        resposta: resposta,

                        dica_usada: dicaUsada

                    })
                }
            );

            const resultado = await response.json();

            if (!resultado.sucesso) {

                mostrarErroServidor(
                    resultado.mensagem ||
                    'Não foi possível verificar sua resposta.'
                );

                answerInput.disabled = false;
                answerButton.disabled = false;
                answerButton.textContent =
                    'Confirmar resposta';

                return;
            }

            processarResultado(resultado);

        } catch (error) {

            console.error(error);

            mostrarErroServidor(
                'Ocorreu um erro de comunicação com o servidor. Tente novamente.'
            );

            answerInput.disabled = false;
            answerButton.disabled = false;
            answerButton.textContent =
                'Confirmar resposta';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Processar resultado
    |--------------------------------------------------------------------------
    */

    function processarResultado(resultado) {

        if (resultado.correta) {

            acertos++;

            pontuacao += Number(
                resultado.pontuacao_obtida || 0
            );

            mostrarFeedback(
                true,
                resultado.mensagem ||
                'Muito bem! Você acertou a questão.',
                resultado.pontuacao_obtida || 0
            );

        } else {

            erros++;

            mostrarFeedback(
                false,
                resultado.mensagem ||
                'Não foi dessa vez. Veja a explicação e tente aprender com o erro.',
                0
            );
        }

        scoreElement.textContent =
            pontuacao;

        correctElement.textContent =
            acertos;

        incorrectElement.textContent =
            erros;

        answerButton.textContent =
            'Resposta registrada';

        nextButton.classList.remove('hidden');

        hintButton.style.display = 'none';
    }


    /*
    |--------------------------------------------------------------------------
    | Feedback
    |--------------------------------------------------------------------------
    */

    function mostrarFeedback(
        correta,
        mensagem,
        pontos
    ) {

        feedback.classList.remove('hidden');

        feedback.classList.remove(
            'feedback-success',
            'feedback-error'
        );

        if (correta) {

            feedback.classList.add(
                'feedback-success'
            );

            feedbackIcon.textContent = '✓';

            feedbackTitle.textContent =
                `Acertou! +${pontos} XP`;

        } else {

            feedback.classList.add(
                'feedback-error'
            );

            feedbackIcon.textContent = '×';

            feedbackTitle.textContent =
                'Quase lá!';
        }

        feedbackMessage.textContent =
            mensagem;
    }


    /*
    |--------------------------------------------------------------------------
    | Próxima questão
    |--------------------------------------------------------------------------
    */

    nextButton.addEventListener('click', () => {

        perguntaAtual++;

        if (perguntaAtual >= perguntas.length) {

            finalizarJogo();

            return;
        }

        answerButton.textContent =
            'Confirmar resposta';

        carregarPergunta();
    });


    /*
    |--------------------------------------------------------------------------
    | Finalizar jogo
    |--------------------------------------------------------------------------
    */

    function finalizarJogo() {

        questionNumberElement.textContent =
            perguntas.length;

        progressText.textContent =
            '100%';

        progressFill.style.width =
            '100%';

        questionElement.textContent =
            '🎉 Você terminou o Caixa Matemático!';

        answerForm.style.display =
            'none';

        hintButton.style.display =
            'none';

        feedback.classList.remove('hidden');

        feedback.classList.remove(
            'feedback-success',
            'feedback-error'
        );

        feedback.classList.add(
            'feedback-success'
        );

        feedbackIcon.textContent =
            '🏆';

        feedbackTitle.textContent =
            'Partida concluída!';

        feedbackMessage.textContent =
            `Você terminou com ${pontuacao} XP, ${acertos} acertos e ${erros} erros.`;

        nextButton.classList.add('hidden');

        atualizarProgresso();

        /*
        |--------------------------------------------------------------
        | Mostra uma pequena pausa antes de voltar ao painel.
        |--------------------------------------------------------------
        */

        setTimeout(() => {

            window.location.href =
                '../../aluno/progresso.php';

        }, 3000);
    }


    /*
    |--------------------------------------------------------------------------
    | Erro de servidor
    |--------------------------------------------------------------------------
    */

    function mostrarErroServidor(mensagem) {

        feedback.classList.remove('hidden');

        feedback.classList.remove(
            'feedback-success'
        );

        feedback.classList.add(
            'feedback-error'
        );

        feedbackIcon.textContent =
            '!';

        feedbackTitle.textContent =
            'Ops!';

        feedbackMessage.textContent =
            mensagem;
    }


    /*
    |--------------------------------------------------------------------------
    | Iniciar jogo
    |--------------------------------------------------------------------------
    */

    carregarPergunta();

});