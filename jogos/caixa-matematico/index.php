<?php

require_once '../../includes/auth.php';
require_once '../../config/config.php';

protegerPagina('aluno');

$usuarioId = usuarioId();

if (!$usuarioId) {
    header('Location: ../../login.php');
    exit;
}

/* ==========================================================
   BUSCAR ALUNO E TURMA
========================================================== */

$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.nome,
        u.turma_id,
        t.nome AS turma_nome,
        t.ano_serie
    FROM usuarios u
    LEFT JOIN turmas t
        ON t.id = u.turma_id
    WHERE u.id = ?
    LIMIT 1
");

$stmt->execute([$usuarioId]);

$aluno = $stmt->fetch();

if (!$aluno) {
    die('Aluno não encontrado.');
}

if (empty($aluno['turma_id']) || empty($aluno['ano_serie'])) {
    die('Seu cadastro ainda não possui uma turma definida.');
}

$turmaId = (int) $aluno['turma_id'];
$anoSerie = (int) $aluno['ano_serie'];


/* ==========================================================
   BUSCAR JOGO
========================================================== */

$stmt = $pdo->prepare("
    SELECT
        id,
        nome,
        descricao
    FROM jogos
    WHERE nome = 'Caixa Matemático'
      AND ativo = TRUE
    LIMIT 1
");

$stmt->execute();

$jogo = $stmt->fetch();

if (!$jogo) {
    die('Caixa Matemático não encontrado.');
}

$jogoId = (int) $jogo['id'];


/* ==========================================================
   VERIFICAR DISPONIBILIDADE PARA A TURMA
========================================================== */

$jogoLiberado = true;

try {

    $stmt = $pdo->prepare("
        SELECT ativo
        FROM jogos_turmas
        WHERE jogo_id = ?
          AND turma_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $jogoId,
        $turmaId
    ]);

    $configuracao = $stmt->fetch();

    if ($configuracao) {
        $jogoLiberado = (bool) $configuracao['ativo'];
    }

} catch (PDOException $e) {

    /*
     * Se a tabela jogos_turmas ainda não estiver disponível,
     * o jogo continua funcionando.
     */

    $jogoLiberado = true;
}

if (!$jogoLiberado) {
    die('Este jogo ainda não está disponível para sua turma.');
}


/* ==========================================================
   BUSCAR PERGUNTAS DA SÉRIE
========================================================== */

$stmt = $pdo->prepare("
    SELECT
        id,
        enunciado,
        conteudo,
        dificuldade,
        pontuacao
    FROM perguntas
    WHERE jogo_id = ?
      AND ano_serie = ?
    ORDER BY id ASC
");

$stmt->execute([
    $jogoId,
    $anoSerie
]);

$perguntasBanco = $stmt->fetchAll();

if (empty($perguntasBanco)) {
    die(
        'Nenhuma pergunta cadastrada para o ' .
        $anoSerie .
        'º ano no Caixa Matemático.'
    );
}


/* ==========================================================
   PREPARAR PERGUNTAS PARA O JAVASCRIPT
========================================================== */

$perguntasPublicas = [];

foreach ($perguntasBanco as $pergunta) {

    $perguntasPublicas[] = [
        'id' => (int) $pergunta['id'],
        'enunciado' => $pergunta['enunciado'],
        'conteudo' => $pergunta['conteudo'],
        'dificuldade' => $pergunta['dificuldade'],
        'pontuacao' => (int) $pergunta['pontuacao']
    ];
}


/* ==========================================================
   DIFICULDADE DA SÉRIE
========================================================== */

$dificuldadeAtual = 'Fácil';

if ($anoSerie === 7) {
    $dificuldadeAtual = 'Médio';
} elseif ($anoSerie === 8) {
    $dificuldadeAtual = 'Difícil';
} elseif ($anoSerie === 9) {
    $dificuldadeAtual = 'Muito difícil';
}

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Caixa Matemático - MathPlay
    </title>

    <link
        rel="stylesheet"
        href="../../assets/css/caixa-matematico.css"
    >

</head>


<body>

<div class="game-page">


    <!-- ======================================================
         HEADER
    ======================================================= -->

    <header class="game-header">

        <a
            href="../../aluno/jogos.php"
            class="back-button"
        >
            ← Voltar aos jogos
        </a>


        <div class="game-brand">

            <div class="game-brand-icon">
                🧮
            </div>

            <div>

                <span>
                    MathPlay
                </span>

                <strong>
                    Caixa Matemático
                </strong>

            </div>

        </div>


        <div class="player-info">

            <span>
                Aluno
            </span>

            <strong>
                <?= htmlspecialchars($aluno['nome']) ?>
            </strong>

        </div>

    </header>


    <!-- ======================================================
         CONTEÚDO
    ======================================================= -->

    <main class="game-container">


        <!-- ==================================================
             STATUS
        =================================================== -->

        <section class="game-status">


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


            <div class="status-card score-card">

                <span class="status-label">
                    Pontuação
                </span>

                <strong id="score">
                    0
                </strong>

            </div>

        </section>


        <!-- ==================================================
             PROGRESSO
        =================================================== -->

        <section class="progress-wrapper">

            <div class="progress-info">

                <span>
                    Progresso
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

        </section>


        <!-- ==================================================
             CARD PRINCIPAL
        =================================================== -->

        <section class="game-card">


            <div class="game-card-header">

                <div>

                    <span
                        class="game-category"
                        id="category"
                    >
                        <?= htmlspecialchars(
                            $perguntasPublicas[0]['conteudo']
                                ?: 'Matemática'
                        ) ?>
                    </span>

                    <h1>
                        Caixa Matemático
                    </h1>

                </div>


                <span
                    class="difficulty-badge"
                    id="difficulty"
                >
                    <?= htmlspecialchars($dificuldadeAtual) ?>
                </span>

            </div>


            <!-- ==============================================
                 CENÁRIO
            =============================================== -->

            <div class="scenario">

                <div class="scenario-icon">
                    💰
                </div>

                <div>

                    <strong>
                        Situação do dia a dia
                    </strong>

                    <p>
                        Resolva o problema usando os valores
                        apresentados na questão.
                    </p>

                </div>

            </div>


            <!-- ==============================================
                 PERGUNTA
            =============================================== -->

            <div class="question-area">

                <span class="question-label">
                    QUESTÃO <span id="question-number-display">1</span>
                </span>

                <h2 id="question">
                    Carregando questão...
                </h2>

            </div>


            <!-- ==============================================
                 DICA
            =============================================== -->

            <div class="hint-area">

                <button
                    type="button"
                    class="hint-button"
                    id="hint-button"
                >
                    💡 Ver dica
                </button>

                <div
                    class="hint-text hidden"
                    id="hint-text"
                ></div>

            </div>


            <!-- ==============================================
                 FORMULÁRIO
            =============================================== -->

            <form
                id="answer-form"
                class="answer-form"
            >

                <label for="answer">
                    Sua resposta
                </label>


                <div class="answer-input-wrapper">

                    <span>
                        R$
                    </span>

                    <input
                        type="text"
                        id="answer"
                        name="answer"
                        placeholder="Digite sua resposta"
                        autocomplete="off"
                        required
                    >
                </div>
                <!-- TECLADO NUMÉRICO PARA O JOGO CAIXA MATEMATICO-->
                <div class="numeric-keypad">
                    <button type="button" class="key-button" data-key="1">1</button>
                    <button type="button" class="key-button" data-key="2">2</button>
                    <button type="button" class="key-button" data-key="3">3</button>

                    <button type="button" class="key-button" data-key="4">4</button>
                    <button type="button" class="key-button" data-key="5">5</button>
                    <button type="button" class="key-button" data-key="6">6</button>

                    <button type="button" class="key-button" data-key="7">7</button>
                    <button type="button" class="key-button" data-key="8">8</button>
                    <button type="button" class="key-button" data-key="9">9</button>

                    <button type="button" class="key-button key-clear" data-key="clear">C</button>
                    <button type="button" class="key-button" data-key="0">0</button>
                    <button type="button" class="key-button key-delete" data-key="delete">←</button>
                </div>


                <button
                    type="submit"
                    class="answer-button"
                    id="answer-button"
                >
                    Confirmar resposta
                </button>

            </form>


            <!-- ==============================================
                 FEEDBACK
            =============================================== -->

            <div
                id="feedback"
                class="feedback hidden"
            >

                <div
                    class="feedback-icon"
                    id="feedback-icon"
                ></div>


                <div>

                    <strong id="feedback-title">
                    </strong>

                    <p id="feedback-message">
                    </p>

                </div>

            </div>


            <!-- ==============================================
                 PRÓXIMA
            =============================================== -->

            <button
                type="button"
                class="next-button hidden"
                id="next-button"
            >
                Próxima questão →
            </button>

        </section>


        <!-- ==================================================
             DICAS EXTRAS
        =================================================== -->

        <section class="game-tips">


            <div class="tip-card">

                <div class="tip-icon">
                    🧠
                </div>

                <div>

                    <strong>
                        Leia com atenção
                    </strong>

                    <p>
                        Identifique os valores importantes
                        antes de fazer a conta.
                    </p>

                </div>

            </div>


            <div class="tip-card">

                <div class="tip-icon">
                    ✏️
                </div>

                <div>

                    <strong>
                        Faça passo a passo
                    </strong>

                    <p>
                        Organize os cálculos para evitar erros.
                    </p>

                </div>

            </div>


            <div class="tip-card">

                <div class="tip-icon">
                    ⭐
                </div>

                <div>

                    <strong>
                        Aprenda com os erros
                    </strong>

                    <p>
                        O feedback ajuda você a entender
                        a questão.
                    </p>

                </div>

            </div>

        </section>


    </main>

</div>


<!-- ==========================================================
     DADOS DO JOGO
=========================================================== -->

<script>

window.MathPlayCaixa = {

    usuarioId: <?= (int) $usuarioId ?>,

    jogoId: <?= (int) $jogoId ?>,

    turmaId: <?= (int) $turmaId ?>,

    anoSerie: <?= (int) $anoSerie ?>,

    turmaNome: <?= json_encode(
        $aluno['turma_nome'],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    ) ?>,

    perguntas: <?= json_encode(
        $perguntasPublicas,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    ) ?>

};

</script>


<script
    src="../../assets/js/caixa-matematico.js"
></script>


<script>

/*
 * Atualiza o número visual da questão.
 * O JS principal continua controlando o jogo.
 */

document.addEventListener('DOMContentLoaded', function () {

    const original =
        document.getElementById('question-number');

    const visual =
        document.getElementById('question-number-display');

    if (!original || !visual) {
        return;
    }

    const observer = new MutationObserver(function () {

        visual.textContent =
            original.textContent;

    });

    observer.observe(
        original,
        {
            childList: true,
            characterData: true,
            subtree: true
        }
    );

    visual.textContent =
        original.textContent;

});

</script>


</body>

</html>