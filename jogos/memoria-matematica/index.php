<?php

require_once '../../includes/auth.php';
require_once '../../config/config.php';

protegerPagina('aluno');

/* =========================
   BUSCAR JOGO
========================= */

$stmt = $pdo->prepare("
    SELECT id, nome, descricao
    FROM jogos
    WHERE nome = 'Memória Matemática'
      AND ativo = TRUE
    LIMIT 1
");

$stmt->execute();
$jogo = $stmt->fetch();

if (!$jogo) {
    die('Jogo não encontrado.');
}

$jogoId = (int) $jogo['id'];

/* =========================
   BUSCAR PERGUNTAS
========================= */

$stmt = $pdo->prepare("
    SELECT
        id,
        enunciado,
        resposta_correta,
        dificuldade,
        pontuacao
    FROM perguntas
    WHERE jogo_id = ?
    ORDER BY
        CASE dificuldade
            WHEN 'facil' THEN 1
            WHEN 'medio' THEN 2
            WHEN 'dificil' THEN 3
        END,
        id
");

$stmt->execute([$jogoId]);

$perguntasBanco = $stmt->fetchAll();

if (empty($perguntasBanco)) {
    die('Nenhuma pergunta cadastrada para este jogo.');
}

/*
 * Não enviamos a resposta correta para o navegador.
 * O PHP/BD continua sendo responsável pela validação.
 */
$perguntasPublicas = [];

foreach ($perguntasBanco as $pergunta) {

    $perguntasPublicas[] = [
        'id' => (int) $pergunta['id'],
        'enunciado' => $pergunta['enunciado'],
        'dificuldade' => $pergunta['dificuldade'],
        'pontuacao' => (int) $pergunta['pontuacao']
    ];
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

    <title>Memória Matemática - MathPlay</title>

    <link
        rel="stylesheet"
        href="../../assets/css/memoria-matematica.css"
    >

</head>

<body>

    <main class="pagina-jogo">

        <!-- CABEÇALHO -->

        <header class="cabecalho-jogo">

            <a
                href="../../aluno/jogos.php"
                class="botao-voltar"
            >
                ← Voltar aos jogos
            </a>

            <div class="titulo-jogo">

                <span class="icone-jogo">🧠</span>

                <div>
                    <h1>Memória Matemática</h1>

                    <p>
                        Encontre os pares entre operações e resultados.
                    </p>
                </div>

            </div>

        </header>


        <!-- STATUS -->

        <section class="status-jogo">

            <div class="status-card">

                <span>🎯</span>

                <div>
                    <small>Pares</small>
                    <strong id="paresEncontrados">0</strong>
                </div>

            </div>


            <div class="status-card">

                <span>⭐</span>

                <div>
                    <small>Pontuação</small>
                    <strong id="pontuacao">0</strong>
                </div>

            </div>


            <div class="status-card">

                <span>✅</span>

                <div>
                    <small>Acertos</small>
                    <strong id="acertos">0</strong>
                </div>

            </div>


            <div class="status-card">

                <span>❌</span>

                <div>
                    <small>Erros</small>
                    <strong id="erros">0</strong>
                </div>

            </div>

        </section>


        <!-- PROGRESSO -->

        <section class="progresso-container">

            <div class="progresso-info">

                <span>Progresso</span>

                <strong id="progressoTexto">
                    0%
                </strong>

            </div>

            <div class="barra-progresso">

                <div
                    id="barraProgresso"
                    class="barra-preenchida"
                    style="width: 0%;"
                ></div>

            </div>

        </section>


        <!-- INSTRUÇÃO -->

        <section class="instrucoes">

            <div class="instrucoes-icone">
                💡
            </div>

            <div>

                <strong>Como jogar?</strong>

                <p>
                    Clique em duas cartas para revelar seu conteúdo.
                    Encontre o resultado correspondente à operação.
                </p>

            </div>

        </section>


        <!-- JOGO -->

        <section class="area-jogo">

            <div class="cabecalho-area">

                <div>

                    <span class="etiqueta">
                        DESAFIO
                    </span>

                    <h2>
                        Encontre os pares matemáticos
                    </h2>

                </div>

                <div
                    id="dificuldadeAtual"
                    class="dificuldade"
                >
                    Fácil
                </div>

            </div>


            <!-- TABULEIRO -->

            <div
                id="tabuleiro"
                class="tabuleiro"
            ></div>


            <!-- FEEDBACK -->

            <div
                id="feedback"
                class="feedback hidden"
            ></div>


            <!-- BOTÃO PRÓXIMA RODADA -->

            <div class="acoes">

                <button
                    type="button"
                    id="reiniciarJogo"
                    class="botao-secundario"
                >
                    🔄 Reiniciar
                </button>

            </div>

        </section>


        <!-- DICA -->

        <section class="dica-card">

            <span>🧠</span>

            <div>

                <strong>Dica</strong>

                <p>
                    Tente memorizar a posição das cartas que você já revelou.
                    Isso ajuda a encontrar os pares mais rapidamente.
                </p>

            </div>

        </section>

    </main>


    <!-- DADOS PARA O JAVASCRIPT -->

    <script>

        window.MathPlayMemoria = {

            perguntas: <?= json_encode(
                $perguntasPublicas,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            ) ?>,

            usuarioId: <?= (int) usuarioId() ?>,

            jogoId: <?= $jogoId ?>

        };

    </script>


    <script
        src="../../assets/js/memoria-matematica.js"
    ></script>

</body>

</html>