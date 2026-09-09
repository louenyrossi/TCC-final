<?php

require_once '../includes/auth.php';
require_once '../config/config.php';

protegerPagina('aluno');

header('Content-Type: application/json; charset=utf-8');

/* =========================
   SOMENTE POST
========================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Método não permitido.'
    ]);

    exit;
}

/* =========================
   LER JSON
========================= */

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

/* =========================
   USUÁRIO
========================= */

$usuarioId = usuarioId();

/* =========================
   DADOS RECEBIDOS
========================= */

$jogoId = isset($dados['jogo_id'])
    ? (int) $dados['jogo_id']
    : 0;

$acertos = isset($dados['acertos'])
    ? (int) $dados['acertos']
    : 0;

$erros = isset($dados['erros'])
    ? (int) $dados['erros']
    : 0;

$pontuacao = isset($dados['pontuacao'])
    ? (int) $dados['pontuacao']
    : 0;


/* =========================
   VALIDAÇÕES
========================= */

if ($jogoId <= 0) {

    http_response_code(400);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Jogo inválido.'
    ]);

    exit;
}

if ($acertos < 0 || $erros < 0 || $pontuacao < 0) {

    http_response_code(400);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Valores inválidos.'
    ]);

    exit;
}


/* =========================
   VERIFICAR JOGO
========================= */

$stmt = $pdo->prepare("
    SELECT id, nome
    FROM jogos
    WHERE id = ?
      AND nome = 'Memória Matemática'
      AND ativo = TRUE
    LIMIT 1
");

$stmt->execute([$jogoId]);

$jogo = $stmt->fetch();

if (!$jogo) {

    http_response_code(404);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Memória Matemática não encontrado.'
    ]);

    exit;
}


/* =========================
   VERIFICAR LIMITE
========================= */

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM perguntas
    WHERE jogo_id = ?
");

$stmt->execute([$jogoId]);

$totalPerguntas = (int) $stmt->fetchColumn();

$totalPares = $totalPerguntas;


/*
 * O jogo considera um par como um acerto.
 *
 * O aluno não pode informar mais acertos
 * do que a quantidade de perguntas.
 */

if ($acertos > $totalPares) {
    $acertos = $totalPares;
}


/* =========================
   XP
========================= */

$xpGanho = $acertos * 10;


/* =========================
   TRANSAÇÃO
========================= */

try {

    $pdo->beginTransaction();


    /* =========================
       SALVAR PARTIDA
    ========================= */

    $stmt = $pdo->prepare("
        INSERT INTO partidas
        (
            usuario_id,
            jogo_id,
            data_inicio,
            data_fim,
            acertos,
            erros,
            pontuacao
        )
        VALUES
        (
            ?,
            ?,
            NOW(),
            NOW(),
            ?,
            ?,
            ?
        )
    ");

    $stmt->execute([
        $usuarioId,
        $jogoId,
        $acertos,
        $erros,
        $pontuacao
    ]);


    /* =========================
       ATUALIZAR XP
    ========================= */

    $stmt = $pdo->prepare("
        UPDATE usuarios
        SET xp = xp + ?
        WHERE id = ?
    ");

    $stmt->execute([
        $xpGanho,
        $usuarioId
    ]);


    /* =========================
       ATUALIZAR NÍVEL
    ========================= */

    $stmt = $pdo->prepare("
        SELECT xp
        FROM usuarios
        WHERE id = ?
    ");

    $stmt->execute([$usuarioId]);

    $novoXp = (int) $stmt->fetchColumn();

    $novoNivel = floor($novoXp / 100) + 1;


    $stmt = $pdo->prepare("
        UPDATE usuarios
        SET nivel = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $novoNivel,
        $usuarioId
    ]);


    /* =========================
       ATUALIZAR PROGRESSO
    ========================= */

    $porcentagem = $totalPares > 0
        ? round(($acertos / $totalPares) * 100, 2)
        : 0;


    /*
     * Atualizamos o progresso de acordo
     * com a melhor porcentagem alcançada.
     */

    $stmt = $pdo->prepare("
        SELECT MAX(porcentagem)
        FROM progresso
        WHERE usuario_id = ?
          AND jogo_id = ?
    ");

    $stmt->execute([
        $usuarioId,
        $jogoId
    ]);

    $melhorProgresso = $stmt->fetchColumn();


    if ($melhorProgresso === false || $melhorProgresso === null) {

        $stmt = $pdo->prepare("
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
                'facil',
                ?,
                ?
            )
        ");

        $status = $porcentagem >= 100
            ? 'concluido'
            : 'em_andamento';

        $stmt->execute([
            $usuarioId,
            $jogoId,
            $status,
            $porcentagem
        ]);

    } elseif ($porcentagem > (float) $melhorProgresso) {

        $stmt = $pdo->prepare("
            UPDATE progresso
            SET
                porcentagem = ?,
                status = ?
            WHERE usuario_id = ?
              AND jogo_id = ?
              AND dificuldade = 'facil'
        ");

        $status = $porcentagem >= 100
            ? 'concluido'
            : 'em_andamento';

        $stmt->execute([
            $porcentagem,
            $status,
            $usuarioId,
            $jogoId
        ]);
    }


    /* =========================
       CONQUISTA:
       PRIMEIRA VITÓRIA
    ========================= */

    if ($acertos > 0) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM conquistas
            WHERE nome = 'Primeira Vitória'
            LIMIT 1
        ");

        $stmt->execute();

        $conquistaId = $stmt->fetchColumn();

        if ($conquistaId) {

            $stmt = $pdo->prepare("
                INSERT IGNORE INTO usuario_conquistas
                (
                    usuario_id,
                    conquista_id
                )
                VALUES (?, ?)
            ");

            $stmt->execute([
                $usuarioId,
                $conquistaId
            ]);
        }
    }


    /* =========================
       CONQUISTA:
       MESTRE DA MATEMÁTICA
    ========================= */

    if ($novoXp >= 100) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM conquistas
            WHERE nome = 'Mestre da Matemática'
            LIMIT 1
        ");

        $stmt->execute();

        $conquistaId = $stmt->fetchColumn();

        if ($conquistaId) {

            $stmt = $pdo->prepare("
                INSERT IGNORE INTO usuario_conquistas
                (
                    usuario_id,
                    conquista_id
                )
                VALUES (?, ?)
            ");

            $stmt->execute([
                $usuarioId,
                $conquistaId
            ]);
        }
    }


    /* =========================
       CONQUISTA:
       MEMÓRIA DE ELEFANTE
    ========================= */

    if ($acertos >= 5) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM conquistas
            WHERE nome = 'Memória de Elefante'
            LIMIT 1
        ");

        $stmt->execute();

        $conquistaId = $stmt->fetchColumn();

        if ($conquistaId) {

            $stmt = $pdo->prepare("
                INSERT IGNORE INTO usuario_conquistas
                (
                    usuario_id,
                    conquista_id
                )
                VALUES (?, ?)
            ");

            $stmt->execute([
                $usuarioId,
                $conquistaId
            ]);
        }
    }


    /* =========================
       ALUNO DEDICADO
    ========================= */

    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT jogo_id)
        FROM partidas
        WHERE usuario_id = ?
          AND data_fim IS NOT NULL
    ");

    $stmt->execute([$usuarioId]);

    $jogosConcluidos = (int) $stmt->fetchColumn();


    if ($jogosConcluidos >= 2) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM conquistas
            WHERE nome = 'Aluno Dedicado'
            LIMIT 1
        ");

        $stmt->execute();

        $conquistaId = $stmt->fetchColumn();

        if ($conquistaId) {

            $stmt = $pdo->prepare("
                INSERT IGNORE INTO usuario_conquistas
                (
                    usuario_id,
                    conquista_id
                )
                VALUES (?, ?)
            ");

            $stmt->execute([
                $usuarioId,
                $conquistaId
            ]);
        }
    }


    $pdo->commit();


    /* =========================
       RESPOSTA
    ========================= */

    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Partida salva com sucesso.',
        'xp_ganho' => $xpGanho,
        'xp_total' => $novoXp,
        'nivel' => $novoNivel,
        'acertos' => $acertos,
        'erros' => $erros,
        'pontuacao' => $pontuacao
    ]);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Não foi possível salvar a partida.'
    ]);

}