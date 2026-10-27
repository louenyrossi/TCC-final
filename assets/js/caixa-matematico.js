
document.addEventListener('DOMContentLoaded', () => {
    const config = window.MathPlayCaixa || {};
    const perguntas = config.perguntas || [];

    let perguntaAtual = 0;
    let pontuacao = 0;
    let acertos = 0;
    let erros = 0;
    let dicaUsada = false;
    let combo = 0;
    let jogoFinalizado = false;

    const questionElement = document.getElementById('question');
    const questionNumberElement = document.getElementById('question-number-display');
    const questionNumberTopElement = document.getElementById('question-number');
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

    // Elementos novos: combo, cenário e recibo.
    const comboElement = document.getElementById('combo');
    const comboMessageElement = document.getElementById('combo-message');

    const productIcon = document.getElementById('product-icon');
    const productTitle = document.getElementById('product-title');
    const productDescription = document.getElementById('product-description');
    const productPrice = document.getElementById('product-price');

    const receipt = document.getElementById('receipt');
    const receiptResult = document.getElementById('receipt-result');
    const receiptStatus = document.getElementById('receipt-status');
    const receiptPoints = document.getElementById('receipt-points');
    const receiptCombo = document.getElementById('receipt-combo');

    // Teclado numérico.
    document.querySelectorAll('.key-button').forEach(botao => {
        botao.addEventListener('click', () => {
            if (answerInput.disabled) return;

            const valor = botao.dataset.key;

            if (valor === 'clear') {
                answerInput.value = '';
                return;
            }

            if (valor === 'delete') {
                answerInput.value = answerInput.value.slice(0, -1);
                return;
            }

            if (valor !== undefined) {
                answerInput.value += valor;
            }

            answerInput.focus();
        });
    });

    if (!perguntas.length) {
        questionElement.textContent = 'Não existem perguntas disponíveis.';
        answerForm.style.display = 'none';
        if (hintButton) hintButton.style.display = 'none';
        if (nextButton) nextButton.style.display = 'none';
        return;
    }

    function formatarDificuldade(dificuldade) {
        const dificuldades = {
            facil: 'Fácil',
            medio: 'Médio',
            dificil: 'Difícil'
        };

        return dificuldades[dificuldade] || dificuldade || 'Normal';
    }

    function atualizarProgresso() {
        const progresso = Math.round((perguntaAtual / perguntas.length) * 100);

        if (progressText) progressText.textContent = `${progresso}%`;
        if (progressFill) progressFill.style.width = `${progresso}%`;
    }

    function atualizarCombo() {
        if (comboElement) comboElement.textContent = `x${combo}`;

        if (comboMessageElement) {
            if (combo === 0) {
                comboMessageElement.textContent = 'Acertou? Comece seu combo!';
            } else if (combo === 1) {
                comboMessageElement.textContent = 'Boa! Continue assim!';
            } else if (combo < 5) {
                comboMessageElement.textContent = 'Sequência de acertos!';
            } else {
                comboMessageElement.textContent = '🔥 Combo incrível!';
            }
        }
    }

    function carregarCenario(pergunta) {
        // Usa dados da pergunta quando existirem; caso contrário,
        // apresenta um cenário genérico sem inventar valores de compra.
        if (productIcon) {
            productIcon.textContent = pergunta.icone || '🛒';
        }

        if (productTitle) {
            productTitle.textContent = pergunta.produto_titulo || 'Cliente no caixa';
        }

        if (productDescription) {
            productDescription.textContent =
                pergunta.produto_descricao ||
                'Analise a situação matemática e registre sua resposta no caixa.';
        }

        if (productPrice) {
            productPrice.textContent =
                pergunta.valor_compra || 'Consulte a questão';
        }
    }

    function carregarPergunta() {
        const pergunta = perguntas[perguntaAtual];

        if (!pergunta) {
            finalizarJogo();
            return;
        }

        if (questionNumberElement) {
            questionNumberElement.textContent = perguntaAtual + 1;
        }

        if (questionNumberTopElement) {
            questionNumberTopElement.textContent = perguntaAtual + 1;
        }

        if (questionTotalElement) {
            questionTotalElement.textContent = `/ ${perguntas.length}`;
        }

        questionElement.textContent = pergunta.enunciado || 'Leia a questão.';

        if (difficultyElement) {
            difficultyElement.textContent = formatarDificuldade(pergunta.dificuldade);
        }

        carregarCenario(pergunta);

        answerInput.value = '';
        answerInput.disabled = false;
        answerButton.disabled = false;
        answerButton.textContent = 'Confirmar resposta';

        feedback.classList.add('hidden');
        nextButton.classList.add('hidden');

        if (receipt) receipt.classList.add('hidden');

        if (hintText) {
            hintText.classList.add('hidden');
            hintText.textContent = '';
        }

        if (hintButton) hintButton.style.display = 'inline-flex';

        dicaUsada = false;
        atualizarProgresso();
        answerInput.focus();
    }

    function gerarDica(enunciado) {
        const texto = (enunciado || '').toLowerCase();

        if (texto.includes('troco') || texto.includes('pagou')) {
            return '💡 Para descobrir o troco, subtraia o valor da compra do valor pago pelo cliente.';
        }

        if (texto.includes('totalizou') || texto.includes('valor total')) {
            return '💡 Some os valores dos produtos para encontrar o total da compra.';
        }

        return '💡 Leia os valores com atenção e identifique qual operação matemática a situação pede.';
    }

    if (hintButton) {
        hintButton.addEventListener('click', async () => {
            const pergunta = perguntas[perguntaAtual];

            if (!pergunta || dicaUsada) return;

            hintButton.disabled = true;

            try {
                const response = await fetch('../../api/caixa-matematico.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        acao: 'usar_dica',
                        jogo_id: config.jogoId,
                        pergunta_id: pergunta.id
                    })
                });

                const resultado = await response.json();

                if (!resultado.sucesso) {
                    alert(resultado.mensagem || 'Não foi possível usar a dica.');
                    return;
                }

                dicaUsada = true;
                hintButton.style.display = 'none';

                if (hintText) {
                    hintText.textContent = gerarDica(pergunta.enunciado);
                    hintText.classList.remove('hidden');
                }
            } catch (error) {
                console.error(error);
                alert('Erro ao usar a dica.');
            } finally {
                if (!dicaUsada) hintButton.disabled = false;
            }
        });
    }

    answerForm.addEventListener('submit', event => {
        event.preventDefault();

        const resposta = answerInput.value.trim();

        if (!resposta || answerInput.disabled) {
            answerInput.focus();
            return;
        }

        verificarResposta(resposta);
    });

    async function verificarResposta(resposta) {
        const pergunta = perguntas[perguntaAtual];

        if (!pergunta || jogoFinalizado) return;

        answerInput.disabled = true;
        answerButton.disabled = true;
        answerButton.textContent = 'Verificando...';

        try {
            const response = await fetch('../../api/caixa-matematico.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    acao: 'responder',
                    usuario_id: config.usuarioId,
                    jogo_id: config.jogoId,
                    pergunta_id: pergunta.id,
                    resposta,
                    dica_usada: dicaUsada
                })
            });

            const resultado = await response.json();

            if (!resultado.sucesso) {
                mostrarErroServidor(
                    resultado.mensagem || 'Não foi possível verificar sua resposta.'
                );

                answerInput.disabled = false;
                answerButton.disabled = false;
                answerButton.textContent = 'Confirmar resposta';
                return;
            }

            processarResultado(resultado);
        } catch (error) {
            console.error(error);
            mostrarErroServidor(
                'Erro de comunicação com o servidor. Tente novamente.'
            );

            answerInput.disabled = false;
            answerButton.disabled = false;
            answerButton.textContent = 'Confirmar resposta';
        }
    }

    function processarResultado(resultado) {
        const correta = Boolean(resultado.correta);
        const pontos = Number(resultado.pontuacao_obtida || 0);

        if (correta) {
            acertos++;
            pontuacao += pontos;
            combo++;
        } else {
            erros++;
            combo = 0;
        }

        atualizarCombo();

        if (scoreElement) scoreElement.textContent = pontuacao;
        if (correctElement) correctElement.textContent = acertos;
        if (incorrectElement) incorrectElement.textContent = erros;

        mostrarFeedback(
            correta,
            resultado.mensagem ||
                (correta
                    ? 'Muito bem! Você acertou a questão.'
                    : 'Não foi dessa vez. Veja a explicação e continue tentando.'),
            pontos
        );

        mostrarRecibo(correta, pontos);

        answerButton.textContent = 'Resposta registrada';
        nextButton.classList.remove('hidden');

        if (hintButton) hintButton.style.display = 'none';
    }

    function mostrarFeedback(correta, mensagem, pontos) {
        feedback.classList.remove('hidden', 'feedback-success', 'feedback-error');

        if (correta) {
            feedback.classList.add('feedback-success');
            feedbackIcon.textContent = '✓';
            feedbackTitle.textContent = `Acertou! +${pontos} XP`;
        } else {
            feedback.classList.add('feedback-error');
            feedbackIcon.textContent = '×';
            feedbackTitle.textContent = 'Quase lá!';
        }

        feedbackMessage.textContent = mensagem;
    }

    function mostrarRecibo(correta, pontos) {
        if (!receipt) return;

        if (receiptResult) {
            receiptResult.textContent = correta ? 'Resposta correta' : 'Resposta incorreta';
        }

        if (receiptStatus) {
            receiptStatus.textContent = correta ? 'Operação aprovada' : 'Operação não aprovada';
        }

        if (receiptPoints) {
            receiptPoints.textContent = `${correta ? '+' : '+'}${correta ? pontos : 0} XP`;
        }

        if (receiptCombo) {
            receiptCombo.textContent = `x${combo}`;
        }

        receipt.classList.remove('hidden');
    }

    nextButton.addEventListener('click', () => {
        if (jogoFinalizado) return;

        perguntaAtual++;

        if (perguntaAtual >= perguntas.length) {
            finalizarJogo();
            return;
        }

        carregarPergunta();
    });

    function finalizarJogo() {
        if (jogoFinalizado) return;
        jogoFinalizado = true;

        if (questionNumberElement) {
            questionNumberElement.textContent = perguntas.length;
        }

        if (progressText) progressText.textContent = '100%';
        if (progressFill) progressFill.style.width = '100%';

        questionElement.textContent = '🎉 Você terminou o Caixa Matemático!';
        answerForm.style.display = 'none';

        if (hintButton) hintButton.style.display = 'none';

        feedback.classList.remove('hidden', 'feedback-error');
        feedback.classList.add('feedback-success');

        feedbackIcon.textContent = '🏆';
        feedbackTitle.textContent = 'Partida concluída!';
        feedbackMessage.textContent =
            `Você terminou com ${pontuacao} XP, ${acertos} acertos e ${erros} erros.`;

        nextButton.classList.add('hidden');

        atualizarProgresso();

        if (receipt) {
            if (receiptResult) receiptResult.textContent = 'Partida concluída';
            if (receiptStatus) receiptStatus.textContent = 'Caixa fechado';
            if (receiptPoints) receiptPoints.textContent = `${pontuacao} XP no total`;
            if (receiptCombo) receiptCombo.textContent = `x${combo}`;
            receipt.classList.remove('hidden');
        }

        // Mantém o comportamento original de voltar ao painel.
        setTimeout(() => {
            window.location.href = '../../aluno/progresso.php';
        }, 3000);
    }

    function mostrarErroServidor(mensagem) {
        feedback.classList.remove('hidden', 'feedback-success');
        feedback.classList.add('feedback-error');

        feedbackIcon.textContent = '!';
        feedbackTitle.textContent = 'Ops!';
        feedbackMessage.textContent = mensagem;
    }

    atualizarCombo();
    carregarPergunta();
});
