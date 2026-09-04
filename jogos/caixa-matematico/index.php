<?php

require_once '../../includes/auth.php';
require_once '../../config/config.php';

protegerPagina('aluno');

$usuarioId = usuarioId();

/*
|--------------------------------------------------------------------------
| Busca o jogo
|--------------------------------------------------------------------------
*/

$stmtJogo = $pdo->prepare("
    SELECT id, nome, descricao, tema
    FROM jogos
    WHERE id = 1
      AND ativo = 1
    LIMIT 1
");

$stmtJogo->execute();
$jogo = $stmtJogo->fetch();

if (!$jogo) {
    die('Jogo não encontrado.');
}

/*
|--------------------------------------------------------------------------
| Busca perguntas
|--------------------------------------------------------------------------
|
| Por enquanto buscamos todas as perguntas do Caixa Matemático.
| O JavaScript fará a navegação entre elas.
|
*/

$stmtPerguntas = $pdo->prepare("
    SELECT
        id,
        enunciado,
        resposta_correta,
        dificuldade,
        pontuacao
    FROM perguntas
    WHERE jogo_id = :jogo_id
    ORDER BY dificuldade, id
");

$stmtPerguntas->execute([
    ':jogo_id' => $jogo['id']
]);

$perguntas = $stmtPerguntas->fetchAll();

if (!$perguntas) {
    die('Ainda não existem perguntas cadastradas para este jogo.');
}

/*
|--------------------------------------------------------------------------
| Converte as perguntas para JSON
|--------------------------------------------------------------------------
|
| A resposta correta NÃO será enviada para o navegador.
| O PHP envia somente os dados necessários para montar a pergunta.
|
*/

$perguntasPublicas = [];

foreach ($perguntas as $pergunta) {

    $perguntasPublicas[] = [
        'id' => (int) $pergunta['id'],
        'enunciado' => $pergunta['enunciado'],
        'dificuldade' => $pergunta['dificuldade'],
        'pontuacao' => (int) $pergunta['pontuacao']
    ];
}

$nomeAluno = htmlspecialchars(nomeUsuario(), ENT_QUOTES, 'UTF-8');

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Caixa Matemático | MathPlay</title>

    <link
        rel="stylesheet"
        href="../../assets/css/caixa-matematico.css"
    >

</head>

<body>

    <main class="game-page">

        <!-- =========================================================
             TOPO
        ========================================================== -->

        <header class="game-header">

            <a
                href="../../aluno/jogos.php"
                class="back-button"
            >
                ← Voltar aos jogos
            </a>

            <div class="game-brand">

                <div class="game-brand-icon">
                    🛒
                </div>

                <div>
                    <span>MathPlay</span>
                    <strong>Caixa Matemático</strong>
                </div>

            </div>

            <div class="player-info">

                <span>Olá,</span>

                <strong>
                    <?= $nomeAluno ?>
                </strong>

            </div>

        </header>


        <!-- =========================================================
             ÁREA DO JOGO
        ========================================================== -->

        <section class="game-container">

            <!-- Painel superior -->

            <div class="game-status">

                <div class="status-card">

                    <span class="status-label">
                        Questão
                    </span>

                    <strong id="question-number">
                        1
                    </strong>

                    <span id="question-total">
                        / <?= count($perguntasPublicas) ?>
                    </span>

                </div>


                <div class="status-card score-card">

                    <span class="status-label">
                        Pontos
                    </span>

                    <strong id="score">
                        0
                    </strong>

                    <span>
                        XP
                    </span>

                </div>


                <div class="status-card">

                    <span class="status-label">
                        Acertos
                    </span>

                    <strong id="correct">
                        0
                    </strong>

                </div>


                <div class="status-card">

                    <span class="status-label">
                        Erros
                    </span>

                    <strong id="incorrect">
                        0
                    </strong>

                </div>

            </div>


            <!-- Barra de progresso -->

            <div class="progress-wrapper">

                <div class="progress-info">

                    <span>
                        Seu progresso
                    </span>

                    <span id="progress-text">
                        0%
                    </span>

                </div>

                <div class="progress-bar">

                    <div
                        class="progress-fill"
                        id="progress-fill"
                    ></div>

                </div>

            </div>


            <!-- =====================================================
                 CARTÃO PRINCIPAL
            ====================================================== -->

            <div class="game-card">

                <div class="game-card-header">

                    <div>

                        <span class="game-category">
                            🧮 Matemática Financeira
                        </span>

                        <h1>
                            Hora de passar no caixa!
                        </h1>

                    </div>

                    <span
                        class="difficulty-badge"
                        id="difficulty"
                    >
                        Fácil
                    </span>

                </div>


                <!-- Situação -->

                <div class="scenario">

                    <div class="scenario-icon">
                        🛒
                    </div>

                    <div>

                        <strong>
                            Você é o caixa!
                        </strong>

                        <p>
                            Resolva a situação abaixo e informe
                            o valor correto.
                        </p>

                    </div>

                </div>


                <!-- Pergunta -->

                <div class="question-area">

                    <span class="question-label">
                        SITUAÇÃO
                    </span>

                    <h2 id="question">
                        Carregando pergunta...
                    </h2>

                </div>


                <!-- Dica -->

                <div
                    class="hint-area"
                    id="hint-area"
                >

                    <button
                        type="button"
                        id="hint-button"
                        class="hint-button"
                    >
                        💡 Preciso de uma dica
                    </button>

                    <p
                        id="hint-text"
                        class="hint-text hidden"
                    ></p>

                </div>


                <!-- Resposta -->

                <form
                    id="answer-form"
                    class="answer-form"
                >

                    <label for="answer">
                        Qual é a sua resposta?
                    </label>

                    <div class="answer-input-wrapper">

                        <span>
                            R$
                        </span>

                        <input
                            type="number"
                            id="answer"
                            name="answer"
                            min="0"
                            step="0.01"
                            placeholder="Digite o valor"
                            autocomplete="off"
                            required
                        >

                    </div>

                    <button
                        type="submit"
                        id="answer-button"
                        class="answer-button"
                    >
                        Confirmar resposta
                    </button>

                </form>


                <!-- Feedback -->

                <div
                    id="feedback"
                    class="feedback hidden"
                    role="alert"
                >

                    <div
                        id="feedback-icon"
                        class="feedback-icon"
                    >
                        ✓
                    </div>

                    <div>

                        <strong id="feedback-title">
                            Muito bem!
                        </strong>

                        <p id="feedback-message">
                            Feedback da questão.
                        </p>

                    </div>

                </div>


                <!-- Próxima pergunta -->

                <button
                    type="button"
                    id="next-button"
                    class="next-button hidden"
                >
                    Próxima questão →
                </button>

            </div>


            <!-- =====================================================
                 INFORMAÇÕES DO JOGO
            ====================================================== -->

            <div class="game-tips">

                <div class="tip-card">

                    <span class="tip-icon">
                        💡
                    </span>

                    <div>

                        <strong>
                            Dica
                        </strong>

                        <p>
                            Leia a situação com atenção antes
                            de fazer a conta.
                        </p>

                    </div>

                </div>


                <div class="tip-card">

                    <span class="tip-icon">
                        ⭐
                    </span>

                    <div>

                        <strong>
                            Pontuação
                        </strong>

                        <p>
                            Questões mais difíceis valem mais XP.
                        </p>

                    </div>

                </div>


                <div class="tip-card">

                    <span class="tip-icon">
                        🧠
                    </span>

                    <div>

                        <strong>
                            Aprenda com os erros
                        </strong>

                        <p>
                            Cada erro apresenta uma explicação
                            para ajudar você a aprender.
                        </p>

                    </div>

                </div>

            </div>

        </section>

    </main>


    <!-- =============================================================
         DADOS PARA O JAVASCRIPT
    ============================================================== -->

    <script>
        window.MathPlayCaixa = {
            perguntas: <?= json_encode(
                $perguntasPublicas,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ) ?>,

            usuarioId: <?= (int) $usuarioId ?>,

            jogoId: <?= (int) $jogo['id'] ?>
        };
    </script>


    <script
        src="../../assets/js/caixa-matematico.js"
    ></script>

</body>

</html>