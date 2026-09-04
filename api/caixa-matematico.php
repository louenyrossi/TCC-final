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


/*
|--------------------------------------------------------------------------
| Lê os dados enviados pelo JavaScript
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| Dados principais
|--------------------------------------------------------------------------
*/

$usuarioId = usuarioId();

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
| Validação básica
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
| Busca a pergunta diretamente no banco
|--------------------------------------------------------------------------
|
| A resposta correta NÃO vem do JavaScript.
|
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
| Normaliza valores numéricos
|--------------------------------------------------------------------------
|
| Permite respostas como:
|
| 5
| 5,00
| 5.00
|
*/

function normalizarNumero(string $valor): ?float
{
    $valor = trim($valor);

    $valor = str_replace('R$', '', $valor);
    $valor = str_replace(' ', '', $valor);

    /*
     * Se tiver vírgula, considera a vírgula como decimal.
     */
    if (str_contains($valor, ',')) {
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
    }

    if (!is_numeric($valor)) {
        return null;
    }

    return round((float) $valor, 2);
}


$respostaAluno = normalizarNumero($resposta);

$respostaCorreta = normalizarNumero(
    $pergunta['resposta_correta']
);

if (
    $respostaAluno === null ||
    $respostaCorreta === null
) {

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Informe um valor numérico válido.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Verifica se a resposta está correta
|--------------------------------------------------------------------------
*/

$correta = abs(
    $respostaAluno - $respostaCorreta
) < 0.01;


/*
|--------------------------------------------------------------------------
| Pontuação
|--------------------------------------------------------------------------
*/

$pontuacaoBase = (int) $pergunta['pontuacao'];

$pontuacaoObtida = 0;

if ($correta) {

    $pontuacaoObtida = $pontuacaoBase;

    /*
     * Se usou dica, reduzimos 20% da pontuação.
     */
    if ($dicaUsada) {
        $pontuacaoObtida = (int) round(
            $pontuacaoObtida * 0.8
        );
    }
}


/*
|--------------------------------------------------------------------------
| Inicia uma nova partida na sessão
|--------------------------------------------------------------------------
|
| Não usamos o usuario_id enviado pelo JavaScript.
| Pegamos o ID diretamente da sessão.
|
*/

if (
    !isset($_SESSION['caixa_partida_id']) ||
    !is_numeric($_SESSION['caixa_partida_id'])
) {

    $stmtPartida = $pdo->prepare("
        INSERT INTO partidas
        (
            usuario_id,
            jogo_id,
            acertos,
            erros,
            pontuacao
        )
        VALUES
        (
            :usuario_id,
            :jogo_id,
            0,
            0,
            0
        )
    ");

    $stmtPartida->execute([
        ':usuario_id' => $usuarioId,
        ':jogo_id' => $jogoId
    ]);

    $_SESSION['caixa_partida_id'] =
        (int) $pdo->lastInsertId();
}

$partidaId =
    (int) $_SESSION['caixa_partida_id'];


/*
|--------------------------------------------------------------------------
| Confere se a partida pertence ao usuário
|--------------------------------------------------------------------------
*/

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
    (int) $partida['jogo_id'] !== $jogoId
) {

    unset($_SESSION['caixa_partida_id']);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Partida inválida. Atualize a página e tente novamente.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Evita responder a mesma pergunta duas vezes
|--------------------------------------------------------------------------
*/

$stmtRespostaExistente = $pdo->prepare("
    SELECT id
    FROM respostas_partida
    WHERE partida_id = :partida_id
      AND pergunta_id = :pergunta_id
    LIMIT 1
");

$stmtRespostaExistente->execute([
    ':partida_id' => $partidaId,
    ':pergunta_id' => $perguntaId
]);

if ($stmtRespostaExistente->fetch()) {

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Essa pergunta já foi respondida nesta partida.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Salva a resposta
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();


    /*
     * Registra a resposta individual.
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
     * Atualiza os totais da partida.
     */

    if ($correta) {

        $stmtAtualizarPartida = $pdo->prepare("
            UPDATE partidas
            SET
                acertos = acertos + 1,
                pontuacao = pontuacao + :pontuacao
            WHERE id = :partida_id
        ");

    } else {

        $stmtAtualizarPartida = $pdo->prepare("
            UPDATE partidas
            SET
                erros = erros + 1
            WHERE id = :partida_id
        ");
    }


    if ($correta) {

        $stmtAtualizarPartida->execute([
            ':pontuacao' => $pontuacaoObtida,
            ':partida_id' => $partidaId
        ]);

    } else {

        $stmtAtualizarPartida->execute([
            ':partida_id' => $partidaId
        ]);
    }


    /*
     * XP do aluno.
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
    }


    /*
     * Atualiza o nível.
     *
     * Cada 100 XP = 1 nível.
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
     * Atualiza progresso da dificuldade.
     */

    $stmtProgresso = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(
                CASE
                    WHEN rp.correta = 1 THEN 1
                    ELSE 0
                END
            ) AS acertos
        FROM respostas_partida rp
        INNER JOIN perguntas p
            ON p.id = rp.pergunta_id
        WHERE rp.partida_id = :partida_id
          AND p.dificuldade = :dificuldade
    ");

    $stmtProgresso->execute([
        ':partida_id' => $partidaId,
        ':dificuldade' => $pergunta['dificuldade']
    ]);

    $dadosProgresso = $stmtProgresso->fetch();

    $totalRespostas =
        (int) ($dadosProgresso['total'] ?? 0);

    $totalAcertos =
        (int) ($dadosProgresso['acertos'] ?? 0);

    $porcentagem = 0;

    if ($totalRespostas > 0) {
        $porcentagem =
            ($totalAcertos / $totalRespostas) * 100;
    }


    /*
     * Verifica se já existe progresso nessa dificuldade.
     */

    $stmtExisteProgresso = $pdo->prepare("
        SELECT id
        FROM progresso
        WHERE usuario_id = :usuario_id
          AND jogo_id = :jogo_id
          AND dificuldade = :dificuldade
        LIMIT 1
    ");

    $stmtExisteProgresso->execute([
        ':usuario_id' => $usuarioId,
        ':jogo_id' => $jogoId,
        ':dificuldade' => $pergunta['dificuldade']
    ]);

    $progressoExistente =
        $stmtExisteProgresso->fetch();


    if ($progressoExistente) {

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
            ':id' => $progressoExistente['id']
        ]);

    } else {

        $stmtCriarProgresso = $pdo->prepare("
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
                'em_andamento',
                :porcentagem
            )
        ");

        $stmtCriarProgresso->execute([
            ':usuario_id' => $usuarioId,
            ':jogo_id' => $jogoId,
            ':dificuldade' => $pergunta['dificuldade'],
            ':porcentagem' => $porcentagem
        ]);
    }


    /*
     * Descobre quantas perguntas existem no jogo.
     */

    $stmtTotalPerguntas = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM perguntas
        WHERE jogo_id = :jogo_id
    ");

    $stmtTotalPerguntas->execute([
        ':jogo_id' => $jogoId
    ]);

    $totalPerguntas =
        (int) $stmtTotalPerguntas->fetch()['total'];


    /*
     * Descobre quantas perguntas já foram respondidas.
     */

    $stmtRespondidas = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM respostas_partida
        WHERE partida_id = :partida_id
    ");

    $stmtRespondidas->execute([
        ':partida_id' => $partidaId
    ]);

    $totalRespondidas =
        (int) $stmtRespondidas->fetch()['total'];


    /*
     * Se todas foram respondidas, encerra a partida.
     */

    $partidaFinalizada = false;

    if (
        $totalPerguntas > 0 &&
        $totalRespondidas >= $totalPerguntas
    ) {

        $stmtFinalizar = $pdo->prepare("
            UPDATE partidas
            SET data_fim = CURRENT_TIMESTAMP
            WHERE id = :partida_id
        ");

        $stmtFinalizar->execute([
            ':partida_id' => $partidaId
        ]);

        $partidaFinalizada = true;
    }


    /*
     * Atualiza conquistas.
     */

    verificarConquistas(
        $pdo,
        $usuarioId,
        $jogoId,
        $partidaId
    );


    $pdo->commit();


    /*
     * Se a partida acabou, removemos o ID da sessão.
     */

    if ($partidaFinalizada) {
        unset($_SESSION['caixa_partida_id']);
    }


    /*
     * Feedback educativo.
     */

    if ($correta) {

        if ($dicaUsada) {

            $mensagem =
                "Muito bem! Você acertou usando a dica. " .
                "Continue praticando para ganhar ainda mais confiança.";

        } else {

            $mensagem =
                "Excelente! Sua resposta está correta. " .
                "Você está dominando essa conta!";
        }

    } else {

        $respostaFormatada =
            number_format(
                $respostaCorreta,
                2,
                ',',
                '.'
            );

        if (
            str_contains(
                strtolower($pergunta['enunciado']),
                'troco'
            )
        ) {

            $mensagem =
                "Vamos revisar: para calcular o troco, " .
                "faça o valor pago menos o valor da compra. " .
                "A resposta correta é R$ {$respostaFormatada}.";

        } else {

            $mensagem =
                "Não tem problema! Revise os valores da situação " .
                "e tente identificar qual operação deve ser feita. " .
                "A resposta correta é {$respostaFormatada}.";
        }
    }


    echo json_encode([
        'sucesso' => true,
        'correta' => $correta,
        'pontuacao_obtida' => $pontuacaoObtida,
        'mensagem' => $mensagem,
        'partida_finalizada' => $partidaFinalizada
    ]);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        'sucesso' => false,
        'mensagem' =>
            'Não foi possível salvar sua resposta. Tente novamente.'
    ]);
}


/*
|--------------------------------------------------------------------------
| FUNÇÃO DE CONQUISTAS
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

    $stmtPartidas = $pdo->prepare("
        SELECT COUNT(*) AS total
        FROM partidas
        WHERE usuario_id = :usuario_id
          AND data_fim IS NOT NULL
    ");

    $stmtPartidas->execute([
        ':usuario_id' => $usuarioId
    ]);

    $totalPartidas =
        (int) $stmtPartidas->fetch()['total'];

    if ($totalPartidas >= 1) {

        desbloquearConquista(
            $pdo,
            $usuarioId,
            'Primeira Vitória'
        );
    }


    /*
     * Mestre da Matemática
     */

    $stmtXP = $pdo->prepare("
        SELECT xp
        FROM usuarios
        WHERE id = :usuario_id
        LIMIT 1
    ");

    $stmtXP->execute([
        ':usuario_id' => $usuarioId
    ]);

    $xp =
        (int) $stmtXP->fetch()['xp'];

    if ($xp >= 100) {

        desbloquearConquista(
            $pdo,
            $usuarioId,
            'Mestre da Matemática'
        );
    }


    /*
     * Caixa Rápido
     *
     * Consideramos bom desempenho:
     * pelo menos 4 acertos em uma partida.
     */

    $stmtCaixa = $pdo->prepare("
        SELECT acertos
        FROM partidas
        WHERE id = :partida_id
        LIMIT 1
    ");

    $stmtCaixa->execute([
        ':partida_id' => $partidaId
    ]);

    $partida =
        $stmtCaixa->fetch();

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
     *
     * Verifica se o aluno já jogou os dois jogos.
     */

    $stmtDoisJogos = $pdo->prepare("
        SELECT COUNT(DISTINCT jogo_id) AS total
        FROM partidas
        WHERE usuario_id = :usuario_id
          AND data_fim IS NOT NULL
    ");

    $stmtDoisJogos->execute([
        ':usuario_id' => $usuarioId
    ]);

    $jogosConcluidos =
        (int) $stmtDoisJogos->fetch()['total'];

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
        INSERT IGNORE INTO usuario_conquistas
        (
            usuario_id,
            conquista_id
        )
        SELECT
            :usuario_id,
            id
        FROM conquistas
        WHERE nome = :nome
        LIMIT 1
    ");

    $stmt->execute([
        ':usuario_id' => $usuarioId,
        ':nome' => $nomeConquista
    ]);
}