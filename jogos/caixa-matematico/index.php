<?php

require_once '../../includes/auth.php';
require_once '../../config/config.php';

protegerPagina('aluno');

/* =========================================================
   USUÁRIO LOGADO
========================================================= */

$usuarioId = usuarioId();

if (!$usuarioId) {
    header('Location: ../../login.php');
    exit;
}

/* =========================================================
   BUSCAR DADOS DO ALUNO E DA TURMA
========================================================= */

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

/* =========================================================
   VERIFICAR TURMA
========================================================= */

if (empty($aluno['turma_id']) || empty($aluno['ano_serie'])) {
    die('Seu cadastro ainda não possui uma turma definida.');
}

$turmaId = (int) $aluno['turma_id'];
$anoSerie = (int) $aluno['ano_serie'];

/* =========================================================
   BUSCAR O JOGO
========================================================= */

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

/* =========================================================
   VERIFICAR SE O JOGO ESTÁ DISPONÍVEL PARA A TURMA
========================================================= */

/*
 * Se a tabela jogos_turmas já estiver sendo utilizada,
 * verificamos se o professor liberou o jogo para esta turma.
 *
 * Caso ainda não exista um registro para a turma,
 * mantemos o jogo disponível para não quebrar o funcionamento
 * durante a fase de testes.
 */

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

    $configuracaoTurma = $stmt->fetch();

    if ($configuracaoTurma) {
        $jogoLiberado = (bool) $configuracaoTurma['ativo'];
    }

} catch (PDOException $e) {

    /*
     * Caso a tabela jogos_turmas ainda não esteja criada,
     * não interrompemos o jogo.
     */
    $jogoLiberado = true;
}

if (!$jogoLiberado) {
    die('Este jogo ainda não está disponível para sua turma.');
}

/* =========================================================
   BUSCAR PERGUNTAS DA SÉRIE DO ALUNO
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        id,
        enunciado,
        conteudo,
        resposta_correta,
        dificuldade,
        pontuacao
    FROM perguntas
    WHERE jogo_id = ?
      AND ano_serie = ?
    ORDER BY
        CASE dificuldade
            WHEN 'facil' THEN 1
            WHEN 'medio' THEN 2
            WHEN 'dificil' THEN 3
            ELSE 4
        END,
        id ASC
");

$stmt->execute([
    $jogoId,
    $anoSerie
]);

$perguntasBanco = $stmt->fetchAll();

/* =========================================================
   VERIFICAR PERGUNTAS
========================================================= */

if (empty($perguntasBanco)) {
    die(
        'Nenhuma pergunta cadastrada para o ' .
        $anoSerie .
        'º ano no Caixa Matemático.'
    );
}

/* =========================================================
   PREPARAR DADOS PARA O JAVASCRIPT
========================================================= */

$perguntasPublicas = [];

foreach ($perguntasBanco as $pergunta) {

    $perguntasPublicas[] = [
        'id' => (int) $pergunta['id'],
        'enunciado' => $pergunta['enunciado'],
        'conteudo' => $pergunta['conteudo'],
        'resposta_correta' => $pergunta['resposta_correta'],
        'dificuldade' => $pergunta['dificuldade'],
        'pontuacao' => (int) $pergunta['pontuacao']
    ];
}

/* =========================================================
   NOME DA DIFICULDADE
========================================================= */

$dificuldadeAtual = 'fácil';

if ($anoSerie === 7) {
    $dificuldadeAtual = 'médio';
} elseif ($anoSerie === 8) {
    $dificuldadeAtual = 'difícil';
} elseif ($anoSerie === 9) {
    $dificuldadeAtual = 'muito difícil';
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

    <title>Caixa Matemático - MathPlay</title>

    <link
        rel="stylesheet"
        href="../../assets/css/caixa-matematico.css"
    >

</head>

<body>

<main class="pagina-jogo">

    <!-- =====================================================
         CABEÇALHO
    ====================================================== -->

    <header class="cabecalho-jogo">

        <a
            href="../../aluno/jogos.php"
            class="botao-voltar"
        >
            ← Voltar aos jogos
        </a>

        <div class="titulo-jogo">

            <span class="icone-jogo">🧮</span>

            <div>

                <h1>Caixa Matemático</h1>

                <p>
                    Resolva situações matemáticas do dia a dia.
                </p>

            </div>

        </div>

    </header>


    <!-- =====================================================
         INFORMAÇÕES DA TURMA
    ====================================================== -->

    <section class="informacoes-aluno">

        <div class="info-item">

            <span class="info-label">
                Turma
            </span>

            <strong>
                <?= htmlspecialchars($aluno['turma_nome']) ?>
            </strong>

        </div>

        <div class="info-item">

            <span class="info-label">
                Série
            </span>

            <strong>
                <?= $anoSerie ?>º ano
            </strong>

        </div>

        <div class="info-item">

            <span class="info-label">
                Dificuldade
            </span>

            <strong>
                <?= htmlspecialchars($dificuldadeAtual) ?>
            </strong>

        </div>

    </section>


    <!-- =====================================================
         STATUS DO JOGO
    ====================================================== -->

    <section class="status-jogo">

        <div class="status-card">

            <span class="status-label">
                Questão
            </span>

            <strong id="questaoAtual">
                1
            </strong>

            <span>
                de <?= count($perguntasPublicas) ?>
            </span>

        </div>


        <div class="status-card">

            <span class="status-label">
                Acertos
            </span>

            <strong id="acertos">
                0
            </strong>

        </div>


        <div class="status-card">

            <span class="status-label">
                Erros
            </span>

            <strong id="erros">
                0
            </strong>

        </div>


        <div class="status-card">

            <span class="status-label">
                Pontuação
            </span>

            <strong id="pontuacao">
                0
            </strong>

        </div>

    </section>


    <!-- =====================================================
         BARRA DE PROGRESSO
    ====================================================== -->

    <section class="progresso-container">

        <div class="progresso-topo">

            <span>
                Progresso
            </span>

            <span id="progressoTexto">
                0%
            </span>

        </div>

        <div class="barra-progresso">

            <div
                class="barra-progresso-preenchida"
                id="barraProgresso"
                style="width: 0%;"
            ></div>

        </div>

    </section>


    <!-- =====================================================
         ÁREA DA PERGUNTA
    ====================================================== -->

    <section class="area-pergunta">

        <div class="cabecalho-pergunta">

            <span
                class="badge-dificuldade"
                id="badgeDificuldade"
            >
                Fácil
            </span>

            <span
                class="badge-conteudo"
                id="badgeConteudo"
            >
                Matemática Financeira
            </span>

        </div>


        <div class="pergunta">

            <span class="numero-pergunta">
                Questão <span id="numeroPergunta">1</span>
            </span>

            <h2 id="enunciado">
                Carregando questão...
            </h2>

        </div>


        <!-- =================================================
             DICA
        ================================================== -->

        <div class="area-dica">

            <button
                type="button"
                id="botaoDica"
                class="botao-dica"
            >
                💡 Ver dica
            </button>

            <div
                id="dica"
                class="dica"
                hidden
            >
                Pense com calma e identifique quais valores
                aparecem na situação.
            </div>

        </div>


        <!-- =================================================
             FORMULÁRIO DE RESPOSTA
        ================================================== -->

        <form
            id="formResposta"
            class="form-resposta"
        >

            <label for="resposta">
                Sua resposta
            </label>

            <div class="campo-resposta">

                <input
                    type="text"
                    id="resposta"
                    name="resposta"
                    autocomplete="off"
                    placeholder="Digite sua resposta"
                    required
                >

                <button
                    type="submit"
                    id="botaoResponder"
                >
                    Responder
                </button>

            </div>

        </form>


        <!-- =================================================
             FEEDBACK
        ================================================== -->

        <div
            id="feedback"
            class="feedback"
            hidden
        >

            <div
                id="feedbackTitulo"
                class="feedback-titulo"
            ></div>

            <div
                id="feedbackMensagem"
                class="feedback-mensagem"
            ></div>

            <div
                id="feedbackResposta"
                class="feedback-resposta"
            ></div>

            <button
                type="button"
                id="botaoProxima"
                class="botao-proxima"
            >
                Próxima questão →
            </button>

        </div>

    </section>


    <!-- =====================================================
         FINAL DO JOGO
    ====================================================== -->

    <section
        id="resultadoFinal"
        class="resultado-final"
        hidden
    >

        <div class="resultado-icone">
            🏆
        </div>

        <h2>
            Partida concluída!
        </h2>

        <p>
            Você terminou todas as questões.
        </p>

        <div class="resultado-cards">

            <div class="resultado-card">

                <span>
                    Acertos
                </span>

                <strong id="resultadoAcertos">
                    0
                </strong>

            </div>

            <div class="resultado-card">

                <span>
                    Erros
                </span>

                <strong id="resultadoErros">
                    0
                </strong>

            </div>

            <div class="resultado-card">

                <span>
                    Pontuação
                </span>

                <strong id="resultadoPontuacao">
                    0
                </strong>

            </div>

        </div>

        <p
            id="resultadoMensagem"
            class="resultado-mensagem"
        ></p>

        <a
            href="../../aluno/progresso.php"
            class="botao-resultado"
        >
            Ver meu progresso
        </a>

    </section>

</main>


<!-- =========================================================
     DADOS PARA O JAVASCRIPT
========================================================== -->

<script>

window.MathPlayCaixa = {

    usuarioId: <?= (int) $usuarioId ?>,

    jogoId: <?= (int) $jogoId ?>,

    turmaId: <?= (int) $turmaId ?>,

    anoSerie: <?= (int) $anoSerie ?>,

    turmaNome: <?= json_encode(
        $aluno['turma_nome'],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) ?>,

    perguntas: <?= json_encode(
        $perguntasPublicas,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) ?>

};

</script>


<!-- =========================================================
     JAVASCRIPT DO JOGO
========================================================== -->

<script
    src="../../assets/js/caixa-matematico.js"
></script>

</body>

</html>