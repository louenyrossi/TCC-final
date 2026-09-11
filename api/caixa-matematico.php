<?php

require_once '../includes/auth.php';
require_once '../config/config.php';

protegerPagina('aluno');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Método não permitido.'
    ]);

    exit;
}

$dados = json_decode(
    file_get_contents('php://input'),
    true
);

if (!is_array($dados)) {
    http_response_code(400);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Dados inválidos.'
    ]);

    exit;
}

$usuarioId = usuarioId();

if (!$usuarioId) {
    http_response_code(401);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Usuário não autenticado.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| DADOS RECEBIDOS
|--------------------------------------------------------------------------
*/

$acao = isset($dados['acao'])
    ? (string) $dados['acao']
    : 'responder';

$jogoId = isset($dados['jogo_id'])
    ? (int) $dados['jogo_id']
    : 0;

$perguntaId = isset($dados['pergunta_id'])
    ? (int) $dados['pergunta_id']
    : 0;

$resposta = isset($dados['resposta'])
    ? trim((string) $dados['resposta'])
    : '';

$dicaUsada = !empty($dados['dica_usada']);


/*
|--------------------------------------------------------------------------
| AÇÃO: USAR DICA
|--------------------------------------------------------------------------
*/

if ($acao === 'usar_dica') {

    if ($jogoId !== 1) {
        http_response_code(400);

        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Jogo inválido.'
        ]);

        exit;
    }

    if ($perguntaId <= 0) {
        http_response_code(400);

        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Pergunta inválida.'
        ]);

        exit;
    }

    /*
     * Confirma se a pergunta pertence ao Caixa Matemático.
     */
    $stmtPergunta = $pdo->prepare("
        SELECT id
        FROM perguntas
        WHERE id = :pergunta_id
          AND jogo_id = :jogo_id
        LIMIT 1
    ");

    $stmtPergunta->execute([
        ':pergunta_id' => $perguntaId,
        ':jogo_id' => $jogoId
    ]);

    if (!$stmtPergunta->fetch()) {
        http_response_code(404);

        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Pergunta não encontrada.'
        ]);

        exit;
    }


    /*
     * Guarda as dicas utilizadas durante a partida
     * para impedir que a mesma pergunta seja cobrada
     * duas vezes.
     */
    if (!isset($_SESSION['caixa_dicas_usadas'])) {
        $_SESSION['caixa_dicas_usadas'] = [];
    }

    if (in_array(
        $perguntaId,
        $_SESSION['caixa_dicas_usadas'],
        true
    )) {

        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'A dica desta pergunta já foi utilizada.'
        ]);

        exit;
    }


    try {

        $pdo->beginTransaction();


        /*
         * Desconta 5 XP.
         *
         * GREATEST impede que o XP fique negativo.
         */
        $stmtXP = $pdo->prepare("
            UPDATE usuarios
            SET xp = GREATEST(xp - 5, 0)
            WHERE id = :usuario_id
        ");

        $stmtXP->execute([
            ':usuario_id' => $usuarioId
        ]);


        /*
         * Recalcula o nível.
         */
        $stmtNivel = $pdo->prepare("
            UPDATE usuarios
            SET nivel = FLOOR(xp / 100) + 1
            WHERE id = :usuario_id
        ");

        $stmtNivel->execute([
            ':usuario_id' => $usuarioId
        ]);


        /*
         * Marca a pergunta como já tendo utilizado dica.
         */
        $_SESSION['caixa_dicas_usadas'][] = $perguntaId;


        /*
         * Busca XP e nível atualizados.
         */
        $stmtAtual = $pdo->prepare("
            SELECT xp, nivel
            FROM usuarios
            WHERE id = :usuario_id
            LIMIT 1
        ");

        $stmtAtual->execute([
            ':usuario_id' => $usuarioId
        ]);

        $usuarioAtual = $stmtAtual->fetch();


        $pdo->commit();


        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Dica utilizada. 5 XP foram descontados.',
            'xp_descontado' => 5,
            'xp_atual' => (int) $usuarioAtual['xp'],
            'nivel_atual' => (int) $usuarioAtual['nivel']
        ]);

        exit;

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        http_response_code(500);

        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Não foi possível utilizar a dica.'
        ]);

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| VALIDAÇÕES DA RESPOSTA
|--------------------------------------------------------------------------
*/

if ($jogoId !== 1) {

    http_response_code(400);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Jogo inválido.'
    ]);

    exit;
}

if ($perguntaId <= 0) {

    http_response_code(400);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Pergunta inválida.'
    ]);

    exit;
}

if ($resposta === '') {

    http_response_code(400);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Informe uma resposta.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| BUSCAR PERGUNTA
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        enunciado,
        resposta_correta,
        dificuldade,
        pontuacao
    FROM perguntas
    WHERE id = :pergunta_id
      AND jogo_id = :jogo_id
    LIMIT 1
");

$stmt->execute([
    ':pergunta_id' => $perguntaId,
    ':jogo_id' => $jogoId
]);

$pergunta = $stmt->fetch();

if (!$pergunta) {

    http_response_code(404);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Pergunta não encontrada.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| NORMALIZAR RESPOSTA NUMÉRICA
|--------------------------------------------------------------------------
|
| Aceita exemplos como:
| 10
| 10,00
| R$ 10,00
| R$10.00
|
*/

function normalizarValorNumerico(string $valor): ?float
{
    $valor = trim($valor);

    $valor = str_replace(
        ['R$', 'r$', ' '],
        '',
        $valor
    );

    if ($valor === '') {
        return null;
    }

    /*
     * Se tiver ponto e vírgula:
     * 1.234,56 -> 1234.56
     */
    if (
        strpos($valor, '.') !== false &&
        strpos($valor, ',') !== false
    ) {

        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);

    } else {

        /*
         * Apenas vírgula:
         * 10,50 -> 10.50
         */
        if (strpos($valor, ',') !== false) {
            $valor = str_replace(',', '.', $valor);
        }
    }

    /*
     * Mantém somente números, ponto e sinal.
     */
    $valor = preg_replace(
        '/[^0-9.\-]/',
        '',
        $valor
    );

    if ($valor === '' || !is_numeric($valor)) {
        return null;
    }

    return round((float) $valor, 2);
}


$respostaAluno = normalizarValorNumerico($resposta);

$respostaCorreta = normalizarValorNumerico(
    (string) $pergunta['resposta_correta']
);


/*
|--------------------------------------------------------------------------
| VERIFICAR RESPOSTA
|--------------------------------------------------------------------------
*/

$correta = false;

if (
    $respostaAluno !== null &&
    $respostaCorreta !== null
) {

    $correta =
        abs($respostaAluno - $respostaCorreta) < 0.01;
}


/*
|--------------------------------------------------------------------------
| PONTUAÇÃO
|--------------------------------------------------------------------------
*/

$pontuacaoBase = (int) $pergunta['pontuacao'];

$pontuacaoObtida = 0;

if ($correta) {

    /*
     * A dica já desconta 5 XP.
     * Não há mais redução de 20% na pontuação.
     */
    $pontuacaoObtida = $pontuacaoBase;
}


/*
|--------------------------------------------------------------------------
| PARTIDA ATUAL
|--------------------------------------------------------------------------
*/

$partidaId = null;


/*
 * Se já existe uma partida na sessão,
 * verifica se ela realmente pertence ao usuário
 * e ao jogo atual.
 */
if (
    isset($_SESSION['caixa_partida_id']) &&
    is_numeric($_SESSION['caixa_partida_id'])
) {

    $partidaId = (int) $_SESSION['caixa_partida_id'];

    $stmtPartida = $pdo->prepare("
        SELECT
            id,
            usuario_id,
            jogo_id,
            acertos,
            erros,
            pontuacao,
            data_fim
        FROM partidas
        WHERE id = :partida_id
        LIMIT 1
    ");

    $stmtPartida->execute([
        ':partida_id' => $partidaId
    ]);

    $partida = $stmtPartida->fetch();

    if (
        !$partida ||
        (int) $partida['usuario_id'] !== $usuarioId ||
        (int) $partida['jogo_id'] !== $jogoId ||
        $partida['data_fim'] !== null
    ) {

        $partidaId = null;

        unset($_SESSION['caixa_partida_id']);
    }
}


/*
|--------------------------------------------------------------------------
| CRIAR PARTIDA
|--------------------------------------------------------------------------
*/

if ($partidaId === null) {

    $stmtCriarPartida = $pdo->prepare("
        INSERT INTO partidas
        (
            usuario_id,
            jogo_id,
            data_inicio,
            acertos,
            erros,
            pontuacao
        )
        VALUES
        (
            :usuario_id,
            :jogo_id,
            NOW(),
            0,
            0,
            0
        )
    ");

    $stmtCriarPartida->execute([
        ':usuario_id' => $usuarioId,
        ':jogo_id' => $jogoId
    ]);

    $partidaId = (int) $pdo->lastInsertId();

    $_SESSION['caixa_partida_id'] = $partidaId;
}


/*
|--------------------------------------------------------------------------
| VERIFICAR SE A QUESTÃO JÁ FOI RESPONDIDA
|--------------------------------------------------------------------------
*/

$stmtDuplicada = $pdo->prepare("
    SELECT id
    FROM respostas_partida
    WHERE partida_id = :partida_id
      AND pergunta_id = :pergunta_id
    LIMIT 1
");

$stmtDuplicada->execute([
    ':partida_id' => $partidaId,
    ':pergunta_id' => $perguntaId
]);

if ($stmtDuplicada->fetch()) {

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Esta pergunta já foi respondida.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| TRANSAÇÃO
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();


    /*
     * Busca novamente os dados da partida.
     */
    $stmtPartida = $pdo->prepare("
        SELECT
            id,
            usuario_id,
            jogo_id,
            acertos,
            erros,
            pontuacao
        FROM partidas
        WHERE id = :partida_id
          AND usuario_id = :usuario_id
          AND jogo_id = :jogo_id
        LIMIT 1
        FOR UPDATE
    ");

    $stmtPartida->execute([
        ':partida_id' => $partidaId,
        ':usuario_id' => $usuarioId,
        ':jogo_id' => $jogoId
    ]);

    $partida = $stmtPartida->fetch();

    if (!$partida) {

        throw new RuntimeException(
            'Partida não encontrada.'
        );
    }


    /*
     * Confirma novamente que não existe
     * resposta duplicada.
     */
    $stmtDuplicada = $pdo->prepare("
        SELECT id
        FROM respostas_partida
        WHERE partida_id = :partida_id
          AND pergunta_id = :pergunta_id
        LIMIT 1
    ");

    $stmtDuplicada->execute([
        ':partida_id' => $partidaId,
        ':pergunta_id' => $perguntaId
    ]);

    if ($stmtDuplicada->fetch()) {

        $pdo->rollBack();

        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Esta pergunta já foi respondida.'
        ]);

        exit;
    }


    /*
     * Salvar resposta.
     */
    $stmtResposta = $pdo->prepare("
        INSERT INTO respostas_partida
        (
            partida_id,
            pergunta_id,
            resposta_dada,
            correta,
            pontuacao_obtida
        )
        VALUES
        (
            :partida_id,
            :pergunta_id,
            :resposta_dada,
            :correta,
            :pontuacao_obtida
        )
    ");

    $stmtResposta->execute([
        ':partida_id' => $partidaId,
        ':pergunta_id' => $perguntaId,
        ':resposta_dada' => $resposta,
        ':correta' => $correta ? 1 : 0,
        ':pontuacao_obtida' => $pontuacaoObtida
    ]);


    /*
     * Atualizar estatísticas da partida.
     */
    $acertosAtual = (int) $partida['acertos'];
    $errosAtual = (int) $partida['erros'];
    $pontuacaoAtual = (int) $partida['pontuacao'];

    if ($correta) {
        $acertosAtual++;
    } else {
        $errosAtual++;
    }

    $pontuacaoAtual += $pontuacaoObtida;


    $stmtAtualizarPartida = $pdo->prepare("
        UPDATE partidas
        SET
            acertos = :acertos,
            erros = :erros,
            pontuacao = :pontuacao
        WHERE id = :partida_id
    ");

    $stmtAtualizarPartida->execute([
        ':acertos' => $acertosAtual,
        ':erros' => $errosAtual,
        ':pontuacao' => $pontuacaoAtual,
        ':partida_id' => $partidaId
    ]);


    /*
     * XP somente quando acertar.
     */
    if ($correta && $pontuacaoObtida > 0) {

        $stmtXP = $pdo->prepare("
            UPDATE usuarios
            SET xp = xp + :xp
            WHERE id = :usuario_id
        ");

        $stmtXP->execute([
            ':xp' => $pontuacaoObtida,
            ':usuario_id' => $usuarioId
        ]);


        /*
         * Atualizar nível.
         */
        $stmtNivel = $pdo->prepare("
            UPDATE usuarios
            SET nivel = FLOOR(xp / 100) + 1
            WHERE id = :usuario_id
        ");

        $stmtNivel->execute([
            ':usuario_id' => $usuarioId
        ]);
    }


    /*
     * Atualizar progresso da dificuldade.
     */
    $dificuldade = $pergunta['dificuldade'];

    $stmtTotalDificuldade = $pdo->prepare("
        SELECT COUNT(*)
        FROM perguntas
        WHERE jogo_id = :jogo_id
          AND dificuldade = :dificuldade
    ");

    $stmtTotalDificuldade->execute([
        ':jogo_id' => $jogoId,
        ':dificuldade' => $dificuldade
    ]);

    $totalDificuldade =
        (int) $stmtTotalDificuldade->fetchColumn();


    $stmtRespondidasDificuldade = $pdo->prepare("
        SELECT COUNT(*)
        FROM respostas_partida rp
        INNER JOIN perguntas p
            ON p.id = rp.pergunta_id
        WHERE rp.partida_id = :partida_id
          AND p.jogo_id = :jogo_id
          AND p.dificuldade = :dificuldade
    ");

    $stmtRespondidasDificuldade->execute([
        ':partida_id' => $partidaId,
        ':jogo_id' => $jogoId,
        ':dificuldade' => $dificuldade
    ]);

    $respondidasDificuldade =
        (int) $stmtRespondidasDificuldade->fetchColumn();


    $porcentagem = 0;

    if ($totalDificuldade > 0) {

        $porcentagem = min(
            100,
            round(
                ($respondidasDificuldade / $totalDificuldade) * 100
            )
        );
    }


    /*
     * Verifica se já existe progresso para esta dificuldade.
     */
    $stmtProgresso = $pdo->prepare("
        SELECT id
        FROM progresso
        WHERE usuario_id = :usuario_id
          AND jogo_id = :jogo_id
          AND dificuldade = :dificuldade
        LIMIT 1
    ");

    $stmtProgresso->execute([
        ':usuario_id' => $usuarioId,
        ':jogo_id' => $jogoId,
        ':dificuldade' => $dificuldade
    ]);

    $progressoExiste = $stmtProgresso->fetch();


    if ($progressoExiste) {

        $status = 'em_andamento';

        if ($porcentagem >= 100) {
            $status = 'concluido';
        }

        $stmtAtualizarProgresso = $pdo->prepare("
            UPDATE progresso
            SET
                porcentagem = :porcentagem,
                status = :status
            WHERE id = :id
        ");

        $stmtAtualizarProgresso->execute([
            ':porcentagem' => $porcentagem,
            ':status' => $status,
            ':id' => $progressoExiste['id']
        ]);

    } else {

        $status = 'em_andamento';

        if ($porcentagem >= 100) {
            $status = 'concluido';
        }

        $stmtInserirProgresso = $pdo->prepare("
            INSERT INTO progresso
            (
                usuario_id,
                jogo_id,
                dificuldade,
                status,
                porcentagem
            )
            VALUES
            (
                :usuario_id,
                :jogo_id,
                :dificuldade,
                :status,
                :porcentagem
            )
        ");

        $stmtInserirProgresso->execute([
            ':usuario_id' => $usuarioId,
            ':jogo_id' => $jogoId,
            ':dificuldade' => $dificuldade,
            ':status' => $status,
            ':porcentagem' => $porcentagem
        ]);
    }


    /*
     * Verificar total de perguntas do jogo.
     */
    $stmtTotalPerguntas = $pdo->prepare("
        SELECT COUNT(*)
        FROM perguntas
        WHERE jogo_id = :jogo_id
    ");

    $stmtTotalPerguntas->execute([
        ':jogo_id' => $jogoId
    ]);

    $totalPerguntas =
        (int) $stmtTotalPerguntas->fetchColumn();


    /*
     * Verificar quantas perguntas foram respondidas
     * nesta partida.
     */
    $stmtRespondidas = $pdo->prepare("
        SELECT COUNT(*)
        FROM respostas_partida
        WHERE partida_id = :partida_id
    ");

    $stmtRespondidas->execute([
        ':partida_id' => $partidaId
    ]);

    $totalRespondidas =
        (int) $stmtRespondidas->fetchColumn();


    /*
     * Se todas as perguntas foram respondidas,
     * finalizar a partida.
     */
    $partidaFinalizada = false;

    if (
        $totalPerguntas > 0 &&
        $totalRespondidas >= $totalPerguntas
    ) {

        $stmtFinalizar = $pdo->prepare("
            UPDATE partidas
            SET data_fim = NOW()
            WHERE id = :partida_id
        ");

        $stmtFinalizar->execute([
            ':partida_id' => $partidaId
        ]);

        $partidaFinalizada = true;
    }


    /*
     * Verificar conquistas.
     */
    verificarConquistas(
        $pdo,
        $usuarioId,
        $jogoId,
        $partidaId
    );


    $pdo->commit();


    /*
     * Limpar sessão da partida somente depois
     * que ela realmente terminou.
     */
    if ($partidaFinalizada) {

        unset($_SESSION['caixa_partida_id']);

        /*
         * A lista de dicas da partida também pode
         * ser limpa ao finalizar.
         */
        unset($_SESSION['caixa_dicas_usadas']);
    }


    /*
     * Feedback didático.
     */
    if ($correta) {

        $mensagem =
            'Muito bem! Sua resposta está correta. ' .
            'Continue assim!';

    } else {

        $mensagem =
            'Não foi dessa vez. ' .
            'Revise os valores apresentados na questão ' .
            'e tente aprender com o erro.';
    }


    /*
     * Retorno para o JavaScript.
     */
    echo json_encode([
        'sucesso' => true,
        'correta' => $correta,
        'pontuacao_obtida' => $pontuacaoObtida,
        'mensagem' => $mensagem,
        'partida_finalizada' => $partidaFinalizada
    ]);

    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Ocorreu um erro ao registrar sua resposta.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| CONQUISTAS
|--------------------------------------------------------------------------
*/

function verificarConquistas(
    PDO $pdo,
    int $usuarioId,
    int $jogoId,
    int $partidaId
): void {

    /*
     * Primeira Vitória
     */
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM partidas
        WHERE usuario_id = :usuario_id
          AND data_fim IS NOT NULL
    ");

    $stmt->execute([
        ':usuario_id' => $usuarioId
    ]);

    $partidasConcluidas =
        (int) $stmt->fetchColumn();

    if ($partidasConcluidas >= 1) {

        desbloquearConquista(
            $pdo,
            $usuarioId,
            'Primeira Vitória'
        );
    }


    /*
     * Mestre da Matemática
     */
    $stmt = $pdo->prepare("
        SELECT xp
        FROM usuarios
        WHERE id = :usuario_id
        LIMIT 1
    ");

    $stmt->execute([
        ':usuario_id' => $usuarioId
    ]);

    $xp = (int) $stmt->fetchColumn();

    if ($xp >= 100) {

        desbloquearConquista(
            $pdo,
            $usuarioId,
            'Mestre da Matemática'
        );
    }


    /*
     * Caixa Rápido
     */
    $stmt = $pdo->prepare("
        SELECT acertos
        FROM partidas
        WHERE id = :partida_id
          AND usuario_id = :usuario_id
        LIMIT 1
    ");

    $stmt->execute([
        ':partida_id' => $partidaId,
        ':usuario_id' => $usuarioId
    ]);

    $partida = $stmt->fetch();

    if (
        $partida &&
        (int) $partida['acertos'] >= 4
    ) {

        desbloquearConquista(
            $pdo,
            $usuarioId,
            'Caixa Rápido'
        );
    }


    /*
     * Aluno Dedicado
     */
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT jogo_id)
        FROM partidas
        WHERE usuario_id = :usuario_id
          AND data_fim IS NOT NULL
    ");

    $stmt->execute([
        ':usuario_id' => $usuarioId
    ]);

    $jogosConcluidos =
        (int) $stmt->fetchColumn();

    if ($jogosConcluidos >= 2) {

        desbloquearConquista(
            $pdo,
            $usuarioId,
            'Aluno Dedicado'
        );
    }
}


/*
|--------------------------------------------------------------------------
| DESBLOQUEAR CONQUISTA
|--------------------------------------------------------------------------
*/

function desbloquearConquista(
    PDO $pdo,
    int $usuarioId,
    string $nomeConquista
): void {

    $stmt = $pdo->prepare("
        SELECT id
        FROM conquistas
        WHERE nome = :nome
        LIMIT 1
    ");

    $stmt->execute([
        ':nome' => $nomeConquista
    ]);

    $conquista = $stmt->fetch();

    if (!$conquista) {
        return;
    }


    /*
     * INSERT IGNORE impede duplicidade.
     */
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO usuario_conquistas
        (
            usuario_id,
            conquista_id,
            data_desbloqueio
        )
        VALUES
        (
            :usuario_id,
            :conquista_id,
            NOW()
        )
    ");

    $stmt->execute([
        ':usuario_id' => $usuarioId,
        ':conquista_id' => $conquista['id']
    ]);
}