<?php

require_once '../../config/config.php';
require_once '../../includes/auth.php';

protegerPagina(['aluno']);

$usuarioId = usuarioId();

/*
|--------------------------------------------------------------------------
| Busca o jogo
|--------------------------------------------------------------------------
*/
$stmtJogo = $pdo->prepare("
    SELECT id, nome, descricao
    FROM jogos
    WHERE nome = 'Memória Matemática'
      AND ativo = 1
    LIMIT 1
");
$stmtJogo->execute();

$jogo = $stmtJogo->fetch();

if (!$jogo) {
    die('Jogo não encontrado.');
}

$jogoId = (int) $jogo['id'];

/*
|--------------------------------------------------------------------------
| Busca as perguntas
|--------------------------------------------------------------------------
*/
$stmtPerguntas = $pdo->prepare("
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
            ELSE 4
        END,
        id
");
$stmtPerguntas->execute([$jogoId]);

$perguntas = $stmtPerguntas->fetchAll();

if (!$perguntas) {
    die('Nenhuma pergunta cadastrada para este jogo.');
}

/*
|--------------------------------------------------------------------------
| Cria o baralho
|--------------------------------------------------------------------------
|
| Cada pergunta gera duas cartas:
|
| - uma carta com a operação
| - uma carta com o resultado
|
| O token identifica exclusivamente aquela carta.
| O servidor também guarda o baralho na sessão para que a API
| consiga validar as jogadas posteriormente.
|
|--------------------------------------------------------------------------
*/

$cartas = [];

foreach ($perguntas as $pergunta) {

    $perguntaId = (int) $pergunta['id'];

    // Carta da operação
    $cartas[] = [
        'token' => bin2hex(random_bytes(8)),
        'pergunta_id' => $perguntaId,
        'tipo' => 'operacao',
        'valor' => $pergunta['enunciado']
    ];

    // Carta do resultado
    $cartas[] = [
        'token' => bin2hex(random_bytes(8)),
        'pergunta_id' => $perguntaId,
        'tipo' => 'resultado',
        'valor' => $pergunta['resposta_correta']
    ];
}

/*
|--------------------------------------------------------------------------
| Embaralha as cartas
|--------------------------------------------------------------------------
*/
shuffle($cartas);

/*
|--------------------------------------------------------------------------
| Guarda o baralho completo no servidor
|--------------------------------------------------------------------------
|
| O JavaScript recebe apenas os dados necessários para desenhar
| as cartas.
|
| A API poderá consultar este baralho através da sessão.
|
|--------------------------------------------------------------------------
*/

$_SESSION['memoria_jogo_id'] = $jogoId;
$_SESSION['memoria_deck'] = $cartas;

/*
|--------------------------------------------------------------------------
| Dados públicos enviados para o JavaScript
|--------------------------------------------------------------------------
*/

$cartasPublicas = [];

foreach ($cartas as $carta) {
    $cartasPublicas[] = [
        'token' => $carta['token'],
        'tipo' => $carta['tipo'],
        'valor' => $carta['valor']
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

    <title>Memória Matemática | MathPlay</title>

    <link
        rel="stylesheet"
        href="../../assets/css/memoria-matematica.css"
    >
</head>

<body>

    <main class="pagina-jogo">

        <!-- Cabeçalho -->
        <header class="cabecalho-jogo">

            <a
                href="../../aluno/jogos.php"
                class="botao-voltar"
            >
                ← Voltar
            </a>

            <div class="titulo-jogo">
                <span class="icone-jogo">🧠</span>

                <div>
                    <h1>Memória Matemática</h1>
                    <p>Encontre os pares e teste sua memória!</p>
                </div>
            </div>

        </header>


        <!-- Status -->
        <section class="status-jogo">

            <div class="status-card">
                <span class="status-label">Acertos</span>
                <strong id="acertos">0</strong>
            </div>

            <div class="status-card">
                <span class="status-label">Erros</span>
                <strong id="erros">0</strong>
            </div>

            <div class="status-card">
                <span class="status-label">Pontuação</span>
                <strong id="pontuacao">0</strong>
            </div>

            <div class="status-card">
                <span class="status-label">Nível</span>
                <strong id="dificuldade">Fácil</strong>
            </div>

        </section>


        <!-- Progresso -->
        <section class="progresso-jogo">

            <div class="progresso-info">
                <span>Progresso</span>

                <strong id="progresso-texto">
                    0%
                </strong>
            </div>

            <div class="barra-progresso">
                <div
                    id="barra-progresso"
                    class="barra-progresso-preenchida"
                    style="width: 0%;"
                ></div>
            </div>

        </section>


        <!-- Instruções -->
        <section class="instrucoes-jogo">

            <div class="icone-instrucao">
                💡
            </div>

            <div>
                <h2>Como jogar?</h2>

                <p>
                    Vire duas cartas e encontre a operação
                    correspondente ao seu resultado.
                </p>
            </div>

        </section>


        <!-- Área do jogo -->
        <section class="area-jogo">

            <div
                id="tabuleiro"
                class="tabuleiro"
                aria-label="Tabuleiro do jogo da memória"
            >
                <!-- As cartas serão criadas pelo JavaScript -->
            </div>

        </section>


        <!-- Feedback -->
        <section
            id="feedback"
            class="feedback-jogo"
            aria-live="polite"
        >
            Encontre os pares matemáticos!
        </section>


        <!-- Botões -->
        <section class="acoes-jogo">

            <button
                type="button"
                id="botao-dica"
                class="botao botao-secundario"
            >
                💡 Dica
            </button>

            <button
                type="button"
                id="botao-reiniciar"
                class="botao botao-primario"
            >
                🔄 Reiniciar
            </button>

        </section>


        <!-- Dica -->
        <section
            id="dica"
            class="cartao-dica"
            hidden
        >
            <strong>💡 Dica</strong>

            <p>
                Procure relacionar cada operação matemática
                com o número que representa seu resultado.
            </p>
        </section>

    </main>


    <!--
    |--------------------------------------------------------------------------
    | Dados necessários para o JavaScript
    |--------------------------------------------------------------------------
    -->

    <script>
        window.MathPlayMemoria = {
            jogoId: <?= (int) $jogoId ?>,
            usuarioId: <?= (int) $usuarioId ?>,
            cartas: <?= json_encode(
                $cartasPublicas,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            ) ?>
        };
    </script>

    <script
        src="../../assets/js/memoria-matematica.js"
    ></script>

</body>

</html>