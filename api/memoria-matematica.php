<?php

require_once '../config/config.php';
require_once '../includes/auth.php';

protegerPagina(['aluno']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Método não permitido.'
    ], JSON_UNESCAPED_UNICODE);

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
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$usuarioId = usuarioId();

$acao = $dados['acao'] ?? '';

$jogoId = isset($dados['jogo_id'])
    ? (int) $dados['jogo_id']
    : 0;


/*
|--------------------------------------------------------------------------
| Confirma que é o jogo correto
|--------------------------------------------------------------------------
*/

$stmtJogo = $pdo->prepare("
    SELECT id, nome
    FROM jogos
    WHERE id = ?
      AND nome = 'Memória Matemática'
      AND ativo = 1
    LIMIT 1
");

$stmtJogo->execute([$jogoId]);

$jogo = $stmtJogo->fetch();

if (!$jogo) {
    http_response_code(404);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Jogo inválido.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| Reiniciar
|--------------------------------------------------------------------------
|
| Remove somente os dados temporários da sessão.
| A partida antiga continua registrada no banco.
|
|--------------------------------------------------------------------------
*/

if ($acao === 'reiniciar') {

    unset($_SESSION['memoria_partida_id']);
    unset($_SESSION['memoria_deck']);
    unset($_SESSION['memoria_jogo_id']);

    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Jogo reiniciado.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| Tentativa
|--------------------------------------------------------------------------
*/

if ($acao !== 'tentativa') {

    http_response_code(400);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Ação inválida.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| Verifica se existe um baralho válido na sessão
|--------------------------------------------------------------------------
*/

if (
    empty($_SESSION['memoria_deck']) ||
    empty($_SESSION['memoria_jogo_id']) ||
    (int) $_SESSION['memoria_jogo_id'] !== $jogoId
) {

    http_response_code(400);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'O tabuleiro expirou. Recarregue a página.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$deck = $_SESSION['memoria_deck'];


/*
|--------------------------------------------------------------------------
| Tokens das cartas
|--------------------------------------------------------------------------
*/

$carta1Token = $dados['carta1'] ?? '';
$carta2Token = $dados['carta2'] ?? '';

if (
    !is_string($carta1Token) ||
    !is_string($carta2Token) ||
    $carta1Token === '' ||
    $carta2Token === ''
) {

    http_response_code(400);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Cartas inválidas.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| Procura as cartas dentro do baralho armazenado no servidor
|--------------------------------------------------------------------------
*/

$carta1 = null;
$carta2 = null;

foreach ($deck as $carta) {

    if ($carta['token'] === $carta1Token) {
        $carta1 = $carta;
    }

    if ($carta['token'] === $carta2Token) {
        $carta2 = $carta;
    }
}

if (!$carta1 || !$carta2) {

    http_response_code(400);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Uma das cartas não pertence a esta partida.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| Não permite selecionar exatamente a mesma carta
|--------------------------------------------------------------------------
*/

if ($carta1Token === $carta2Token) {

    http_response_code(400);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Você precisa escolher duas cartas diferentes.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| Verifica se as cartas formam um par
|--------------------------------------------------------------------------
*/

$parCorreto =
    (int) $carta1['pergunta_id'] === (int) $carta2['pergunta_id']
    &&
    $carta1['tipo'] !== $carta2['tipo'];

$perguntaId = (int) $carta1['pergunta_id'];


/*
|--------------------------------------------------------------------------
| Busca a pergunta verdadeira no banco
|--------------------------------------------------------------------------
*/

$stmtPergunta = $pdo->prepare("
    SELECT
        id,
        enunciado,
        resposta_correta,
        dificuldade,
        pontuacao
    FROM perguntas
    WHERE id = ?
      AND jogo_id = ?
    LIMIT 1
");

$stmtPergunta->execute([
    $perguntaId,
    $jogoId
]);

$pergunta = $stmtPergunta->fetch();

if (!$pergunta) {

    http_response_code(400);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Pergunta inválida.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| Cria ou recupera a partida
|--------------------------------------------------------------------------
*/

$partidaId = $_SESSION['memoria_partida_id'] ?? null;

if ($partidaId) {

    $stmtPartida = $pdo->prepare("
        SELECT *
        FROM partidas
        WHERE id = ?
          AND usuario_id = ?
          AND jogo_id = ?
        LIMIT 1
    ");

    $stmtPartida->execute([
        (int) $partidaId,
        $usuarioId,
        $jogoId
    ]);

    $partida = $stmtPartida->fetch();

    if (
        !$partida ||
        !empty($partida['data_fim'])
    ) {
        unset($_SESSION['memoria_partida_id']);
        $partida = null;
    }
}

if (!$partida) {

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
            ?,
            ?,
            NOW(),
            0,
            0,
            0
        )
    ");

    $stmtCriarPartida->execute([
        $usuarioId,
        $jogoId
    ]);

    $partidaId = (int) $pdo->lastInsertId();

    $_SESSION['memoria_partida_id'] = $partidaId;

    $partida = [
        'id' => $partidaId,
        'acertos' => 0,
        'erros' => 0,
        'pontuacao' => 0
    ];
}


/*
|--------------------------------------------------------------------------
| Impede ganhar pontos duas vezes pelo mesmo par
|--------------------------------------------------------------------------
*/

$stmtJaRespondida = $pdo->prepare("
    SELECT id
    FROM respostas_partida
    WHERE partida_id = ?
      AND pergunta_id = ?
      AND correta = 1
    LIMIT 1
");

$stmtJaRespondida->execute([
    $partidaId,
    $perguntaId
]);

if ($stmtJaRespondida->fetch()) {

    http_response_code(400);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Esse par já foi encontrado.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| Dados atuais da partida
|--------------------------------------------------------------------------
*/

$acertosAtuais = (int) $partida['acertos'];
$errosAtuais = (int) $partida['erros'];
$pontuacaoAtual = (int) $partida['pontuacao'];


/*
|--------------------------------------------------------------------------
| Processa a tentativa
|--------------------------------------------------------------------------
*/

if ($parCorreto) {

    $pontos = (int) $pergunta['pontuacao'];

    $respostaDada =
        'par:' .
        $carta1Token .
        ':' .
        $carta2Token;

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
            ?,
            ?,
            ?,
            1,
            ?
        )
    ");

    $stmtResposta->execute([
        $partidaId,
        $perguntaId,
        $respostaDada,
        $pontos
    ]);

    $acertosAtuais++;
    $pontuacaoAtual += $pontos;

    /*
    |--------------------------------------------------------------------------
    | Atualiza partida
    |--------------------------------------------------------------------------
    */

    $stmtAtualizarPartida = $pdo->prepare("
        UPDATE partidas
        SET
            acertos = ?,
            pontuacao = ?
        WHERE id = ?
          AND usuario_id = ?
    ");

    $stmtAtualizarPartida->execute([
        $acertosAtuais,
        $pontuacaoAtual,
        $partidaId,
        $usuarioId
    ]);


    /*
    |--------------------------------------------------------------------------
    | XP
    |--------------------------------------------------------------------------
    |
    | Neste jogo, cada par correto concede XP igual à pontuação
    | da pergunta.
    |
    |--------------------------------------------------------------------------
    */

    $xpGanho = $pontos;

    $stmtXP = $pdo->prepare("
        UPDATE usuarios
        SET xp = xp + ?
        WHERE id = ?
    ");

    $stmtXP->execute([
        $xpGanho,
        $usuarioId
    ]);

    /*
    |--------------------------------------------------------------------------
    | Calcula nível
    |--------------------------------------------------------------------------
    */

    $stmtUsuario = $pdo->prepare("
        SELECT xp
        FROM usuarios
        WHERE id = ?
        LIMIT 1
    ");

    $stmtUsuario->execute([$usuarioId]);

    $usuario = $stmtUsuario->fetch();

    $xpAtual = (int) ($usuario['xp'] ?? 0);

    $nivel = max(
        1,
        (int) floor($xpAtual / 100) + 1
    );

    $stmtNivel = $pdo->prepare("
        UPDATE usuarios
        SET nivel = ?
        WHERE id = ?
    ");

    $stmtNivel->execute([
        $nivel,
        $usuarioId
    ]);

    $_SESSION['xp'] = $xpAtual;
    $_SESSION['nivel'] = $nivel;

} else {

    /*
    |--------------------------------------------------------------------------
    | Tentativa incorreta
    |--------------------------------------------------------------------------
    */

    $respostaDada =
        'tentativa:' .
        $carta1Token .
        ':' .
        $carta2Token;

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
            ?,
            ?,
            ?,
            0,
            0
        )
    ");

    $stmtResposta->execute([
        $partidaId,
        $perguntaId,
        $respostaDada
    ]);

    $errosAtuais++;

    $stmtAtualizarPartida = $pdo->prepare("
        UPDATE partidas
        SET erros = ?
        WHERE id = ?
          AND usuario_id = ?
    ");

    $stmtAtualizarPartida->execute([
        $errosAtuais,
        $partidaId,
        $usuarioId
    ]);

    $xpGanho = 0;
}


/*
|--------------------------------------------------------------------------
| Verifica se todos os pares foram encontrados
|--------------------------------------------------------------------------
*/

$stmtTotalPerguntas = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM perguntas
    WHERE jogo_id = ?
");

$stmtTotalPerguntas->execute([$jogoId]);

$totalPares = (int) $stmtTotalPerguntas->fetchColumn();


$stmtTotalAcertos = $pdo->prepare("
    SELECT COUNT(DISTINCT pergunta_id)
    FROM respostas_partida
    WHERE partida_id = ?
      AND correta = 1
");

$stmtTotalAcertos->execute([$partidaId]);

$totalAcertosPartida = (int) $stmtTotalAcertos->fetchColumn();

$partidaFinalizada = false;


/*
|--------------------------------------------------------------------------
| Finaliza partida
|--------------------------------------------------------------------------
*/

if (
    $totalPares > 0 &&
    $totalAcertosPartida >= $totalPares
) {

    $stmtFinalizar = $pdo->prepare("
        UPDATE partidas
        SET data_fim = NOW()
        WHERE id = ?
          AND usuario_id = ?
    ");

    $stmtFinalizar->execute([
        $partidaId,
        $usuarioId
    ]);

    $partidaFinalizada = true;

    unset($_SESSION['memoria_partida_id']);
    unset($_SESSION['memoria_deck']);
    unset($_SESSION['memoria_jogo_id']);
}


/*
|--------------------------------------------------------------------------
| Atualiza progresso por dificuldade
|--------------------------------------------------------------------------
*/

$dificuldades = [
    'facil',
    'medio',
    'dificil'
];

foreach ($dificuldades as $dificuldade) {

    $stmtTotalDificuldade = $pdo->prepare("
        SELECT COUNT(*)
        FROM perguntas
        WHERE jogo_id = ?
          AND dificuldade = ?
    ");

    $stmtTotalDificuldade->execute([
        $jogoId,
        $dificuldade
    ]);

    $totalDificuldade =
        (int) $stmtTotalDificuldade->fetchColumn();

    if ($totalDificuldade <= 0) {
        continue;
    }


    /*
    | Conta perguntas diferentes já acertadas pelo aluno
    */

    $stmtAcertosDificuldade = $pdo->prepare("
        SELECT COUNT(DISTINCT rp.pergunta_id)
        FROM respostas_partida rp
        INNER JOIN partidas p
            ON p.id = rp.partida_id
        INNER JOIN perguntas q
            ON q.id = rp.pergunta_id
        WHERE p.usuario_id = ?
          AND p.jogo_id = ?
          AND rp.correta = 1
          AND q.dificuldade = ?
    ");

    $stmtAcertosDificuldade->execute([
        $usuarioId,
        $jogoId,
        $dificuldade
    ]);

    $acertosDificuldade =
        (int) $stmtAcertosDificuldade->fetchColumn();


    $porcentagem = (int) round(
        ($acertosDificuldade / $totalDificuldade) * 100
    );

    $porcentagem = min(
        100,
        max(0, $porcentagem)
    );


    if ($porcentagem >= 100) {
        $status = 'concluido';
    } elseif ($porcentagem > 0) {
        $status = 'em_andamento';
    } else {
        $status = 'disponivel';
    }


    /*
    | Cria ou atualiza o progresso
    */

    $stmtProgresso = $pdo->prepare("
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
            ?,
            ?,
            ?,
            ?,
            ?
        )
        ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            porcentagem = VALUES(porcentagem)
    ");

    $stmtProgresso->execute([
        $usuarioId,
        $jogoId,
        $dificuldade,
        $status,
        $porcentagem
    ]);
}


/*
|--------------------------------------------------------------------------
| Conquistas
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
        WHERE nome = ?
        LIMIT 1
    ");

    $stmt->execute([$nomeConquista]);

    $conquista = $stmt->fetch();

    if (!$conquista) {
        return;
    }

    $stmtInserir = $pdo->prepare("
        INSERT IGNORE INTO usuario_conquistas
        (
            usuario_id,
            conquista_id,
            data_desbloqueio
        )
        VALUES
        (
            ?,
            ?,
            NOW()
        )
    ");

    $stmtInserir->execute([
        $usuarioId,
        (int) $conquista['id']
    ]);
}


/*
|--------------------------------------------------------------------------
| Primeira Vitória
|--------------------------------------------------------------------------
*/

if ($partidaFinalizada && $acertosAtuais > 0) {

    desbloquearConquista(
        $pdo,
        $usuarioId,
        'Primeira Vitória'
    );
}


/*
|--------------------------------------------------------------------------
| Memória de Elefante
|--------------------------------------------------------------------------
*/

if ($acertosAtuais >= 5) {

    desbloquearConquista(
        $pdo,
        $usuarioId,
        'Memória de Elefante'
    );
}


/*
|--------------------------------------------------------------------------
| Mestre da Matemática
|--------------------------------------------------------------------------
*/

$stmtXPFinal = $pdo->prepare("
    SELECT xp
    FROM usuarios
    WHERE id = ?
    LIMIT 1
");

$stmtXPFinal->execute([$usuarioId]);

$xpFinal = (int) $stmtXPFinal->fetchColumn();

if ($xpFinal >= 100) {

    desbloquearConquista(
        $pdo,
        $usuarioId,
        'Mestre da Matemática'
    );
}


/*
|--------------------------------------------------------------------------
| Aluno Dedicado
|--------------------------------------------------------------------------
*/

$stmtJogosConcluidos = $pdo->prepare("
    SELECT COUNT(DISTINCT jogo_id)
    FROM partidas
    WHERE usuario_id = ?
      AND data_fim IS NOT NULL
");

$stmtJogosConcluidos->execute([$usuarioId]);

$jogosConcluidos =
    (int) $stmtJogosConcluidos->fetchColumn();

if ($jogosConcluidos >= 2) {

    desbloquearConquista(
        $pdo,
        $usuarioId,
        'Aluno Dedicado'
    );
}


/*
|--------------------------------------------------------------------------
| Dificuldade atual apenas para feedback visual
|--------------------------------------------------------------------------
*/

if ($acertosAtuais <= 2) {
    $dificuldadeAtual = 'Fácil';
} elseif ($acertosAtuais <= 5) {
    $dificuldadeAtual = 'Médio';
} else {
    $dificuldadeAtual = 'Difícil';
}


/*
|--------------------------------------------------------------------------
| Mensagem
|--------------------------------------------------------------------------
*/

if ($parCorreto) {

    $mensagem = "🎉 Par correto! +{$pontos} pontos.";

} else {

    $mensagem =
        '❌ Essas cartas não formam um par. Tente novamente.';
}


/*
|--------------------------------------------------------------------------
| Resposta final
|--------------------------------------------------------------------------
*/

echo json_encode([
    'sucesso' => true,
    'correta' => $parCorreto,

    'acertos' => $acertosAtuais,
    'erros' => $errosAtuais,
    'pontuacao' => $pontuacaoAtual,

    'xp_ganho' => $xpGanho ?? 0,

    'dificuldade' => $dificuldadeAtual,

    'partida_finalizada' => $partidaFinalizada,

    'mensagem' => $mensagem
], JSON_UNESCAPED_UNICODE);

exit;