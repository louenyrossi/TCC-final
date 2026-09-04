USE b14_42774124_tcc_final;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('aluno', 'professor', 'admin') NOT NULL DEFAULT 'aluno',
    nivel INT NOT NULL DEFAULT 1,
    xp INT NOT NULL DEFAULT 0,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE jogos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    tema VARCHAR(100) NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE perguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jogo_id INT NOT NULL,
    enunciado TEXT NOT NULL,
    resposta_correta VARCHAR(255) NOT NULL,
    dificuldade ENUM('facil', 'medio', 'dificil') NOT NULL,
    pontuacao INT NOT NULL DEFAULT 10,

    FOREIGN KEY (jogo_id) REFERENCES jogos(id)
);

CREATE TABLE partidas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    jogo_id INT NOT NULL,
    data_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_fim TIMESTAMP NULL,
    acertos INT NOT NULL DEFAULT 0,
    erros INT NOT NULL DEFAULT 0,
    pontuacao INT NOT NULL DEFAULT 0,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (jogo_id) REFERENCES jogos(id)
);

CREATE TABLE conquistas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT NOT NULL,
    tipo VARCHAR(50) NOT NULL
);

CREATE TABLE usuario_conquistas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    conquista_id INT NOT NULL,
    data_desbloqueio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (conquista_id) REFERENCES conquistas(id),

    UNIQUE (usuario_id, conquista_id)
);

CREATE TABLE progresso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    jogo_id INT NOT NULL,
    status ENUM('bloqueado', 'disponivel', 'em_andamento', 'concluido')
        NOT NULL DEFAULT 'bloqueado',
    porcentagem DECIMAL(5,2) NOT NULL DEFAULT 0,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (jogo_id) REFERENCES jogos(id),

    UNIQUE (usuario_id, jogo_id)
);

INSERT INTO jogos (nome, descricao, tema)
VALUES
(
    'Caixa Matemático',
    'Jogo onde o aluno atua como caixa de mercado, calcula o valor das compras e fornece o troco correto.',
    'Matemática Financeira'
),
(
    'Memória Matemática',
    'Jogo de memória onde o aluno relaciona operações matemáticas com seus respectivos resultados.',
    'Operações Matemáticas'
);

INSERT INTO perguntas
(jogo_id, enunciado, resposta_correta, dificuldade, pontuacao)
VALUES
(1, 'Uma pessoa comprou um produto por R$ 15,00 e pagou com R$ 20,00. Qual é o troco?', '5', 'facil', 10),
(1, 'Uma pessoa comprou produtos que custam R$ 12,00 e R$ 8,00. Pagou com R$ 30,00. Qual é o troco?', '10', 'facil', 10),
(1, 'Uma compra totalizou R$ 37,00 e o cliente pagou com R$ 50,00. Qual é o troco?', '13', 'medio', 15),
(1, 'Uma compra possui produtos de R$ 25,00, R$ 15,00 e R$ 10,00. Qual é o valor total?', '50', 'medio', 15),
(1, 'Uma compra totalizou R$ 68,00 e o cliente pagou com R$ 100,00. Qual é o troco?', '32', 'dificil', 20);

INSERT INTO perguntas
(jogo_id, enunciado, resposta_correta, dificuldade, pontuacao)
VALUES
(2, '2 + 3 = ?', '5', 'facil', 10),
(2, '8 - 3 = ?', '5', 'facil', 10),
(2, '4 x 3 = ?', '12', 'facil', 10),
(2, '20 / 4 = ?', '5', 'facil', 10),
(2, '7 + 8 = ?', '15', 'medio', 15),
(2, '9 x 6 = ?', '54', 'medio', 15),
(2, '50 - 27 = ?', '23', 'medio', 15),
(2, '72 / 8 = ?', '9', 'dificil', 20);

INSERT INTO conquistas (nome, descricao, tipo)
VALUES
(
    'Primeira Vitória',
    'Conquiste sua primeira partida.',
    'partida'
),
(
    'Mestre da Matemática',
    'Alcance 100 XP.',
    'xp'
),
(
    'Caixa Rápido',
    'Tenha um bom desempenho no Caixa Matemático.',
    'jogo'
),
(
    'Memória de Elefante',
    'Tenha um bom desempenho no Memória Matemática.',
    'jogo'
),
(
    'Aluno Dedicado',
    'Jogue os dois jogos disponíveis.',
    'progresso'
);

INSERT INTO usuarios
(nome, email, senha, tipo, nivel, xp)
VALUES
('Aluno Teste', 'aluno@mathplay.com', '123456', 'aluno', 1, 0),
('Professor Teste', 'professor@mathplay.com', '123456', 'professor', 1, 0),
('Administrador', 'admin@mathplay.com', '123456', 'admin', 1, 0);

INSERT INTO progresso
(usuario_id, jogo_id, status, porcentagem)
VALUES
(1, 1, 'disponivel', 0),
(1, 2, 'disponivel', 0);

INSERT INTO usuario_conquistas
(usuario_id, conquista_id)
VALUES
(1, 1);

INSERT INTO partidas
(usuario_id, jogo_id, acertos, erros, pontuacao)
VALUES
(1, 1, 3, 1, 30),
(1, 2, 5, 2, 50);

USE b14_42774124_tcc_final;

-- =========================================================
-- 1. CRIAR TABELA DE TURMAS
-- =========================================================

CREATE TABLE IF NOT EXISTS turmas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(10) NOT NULL,
    ano_serie INT NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE
);


-- =========================================================
-- 2. CADASTRAR AS 8 TURMAS
-- =========================================================

INSERT INTO turmas (nome, ano_serie) VALUES
('6º A', 6),
('6º B', 6),
('7º A', 7),
('7º B', 7),
('8º A', 8),
('8º B', 8),
('9º A', 9),
('9º B', 9);


-- =========================================================
-- 3. ADICIONAR TURMA AO USUÁRIO
-- =========================================================

ALTER TABLE usuarios
ADD COLUMN turma_id INT NULL,
ADD CONSTRAINT fk_usuario_turma
FOREIGN KEY (turma_id) REFERENCES turmas(id);


-- =========================================================
-- 4. CRIAR RELAÇÃO PROFESSOR ↔ TURMAS
-- =========================================================

CREATE TABLE IF NOT EXISTS professor_turmas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,

    FOREIGN KEY (professor_id) REFERENCES usuarios(id),
    FOREIGN KEY (turma_id) REFERENCES turmas(id),

    UNIQUE (professor_id, turma_id)
);


-- =========================================================
-- 5. ADICIONAR ANO/SÉRIE ÀS PERGUNTAS
-- =========================================================

ALTER TABLE perguntas
ADD COLUMN ano_serie INT NOT NULL DEFAULT 6
AFTER jogo_id;


-- =========================================================
-- 6. ADICIONAR CONTEÚDO/TEMA ESPECÍFICO À PERGUNTA
-- =========================================================

ALTER TABLE perguntas
ADD COLUMN conteudo VARCHAR(100) NULL
AFTER enunciado;


-- =========================================================
-- 7. ADICIONAR DIFICULDADE AO PROGRESSO
-- =========================================================

ALTER TABLE progresso
ADD COLUMN dificuldade ENUM('facil', 'medio', 'dificil')
NOT NULL DEFAULT 'facil'
AFTER jogo_id;


-- =========================================================
-- 8. ALTERAR A REGRA DE PROGRESSO
-- =========================================================
-- O progresso agora será separado por:
-- usuário + jogo + dificuldade
--
-- Primeiro removemos a restrição antiga.
-- Depois criamos a nova.


ALTER TABLE progresso
DROP INDEX usuario_id;

ALTER TABLE progresso
ADD UNIQUE (usuario_id, jogo_id, dificuldade);


-- =========================================================
-- 9. CRIAR HISTÓRICO DETALHADO DAS RESPOSTAS
-- =========================================================

CREATE TABLE IF NOT EXISTS respostas_partida (
    id INT AUTO_INCREMENT PRIMARY KEY,

    partida_id INT NOT NULL,
    pergunta_id INT NOT NULL,

    resposta_dada VARCHAR(255) NOT NULL,
    correta BOOLEAN NOT NULL DEFAULT FALSE,
    pontuacao_obtida INT NOT NULL DEFAULT 0,

    data_resposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (partida_id)
        REFERENCES partidas(id)
        ON DELETE CASCADE,

    FOREIGN KEY (pergunta_id)
        REFERENCES perguntas(id)
        ON DELETE CASCADE
);


-- =========================================================
-- 10. ATUALIZAR AS PERGUNTAS EXISTENTES
-- =========================================================
-- As perguntas que você já possui ficarão inicialmente
-- associadas ao 6º ano.
--
-- Aqui também adicionamos os conteúdos correspondentes.


UPDATE perguntas
SET ano_serie = 6
WHERE jogo_id = 1
AND id IN (1, 2);


UPDATE perguntas
SET ano_serie = 7
WHERE jogo_id = 1
AND id IN (3);


UPDATE perguntas
SET ano_serie = 8
WHERE jogo_id = 1
AND id IN (4);


UPDATE perguntas
SET ano_serie = 9
WHERE jogo_id = 1
AND id IN (5);


UPDATE perguntas
SET conteudo = 'Matemática Financeira'
WHERE jogo_id = 1;


UPDATE perguntas
SET conteudo = 'Operações Matemáticas'
WHERE jogo_id = 2;


-- =========================================================
-- 11. CADASTRAR O ALUNO DE TESTE NA TURMA 6º A
-- =========================================================

UPDATE usuarios
SET turma_id = (
    SELECT id
    FROM turmas
    WHERE nome = '6º A'
    LIMIT 1
)
WHERE email = 'aluno@mathplay.com';


-- =========================================================
-- 12. VINCULAR O PROFESSOR DE TESTE ÀS TURMAS
-- =========================================================
-- Professor Teste ficará responsável pelas 8 turmas
-- para facilitar os testes do TCC.


INSERT IGNORE INTO professor_turmas (professor_id, turma_id)
SELECT
    u.id,
    t.id
FROM usuarios u
CROSS JOIN turmas t
WHERE u.email = 'professor@mathplay.com'
AND u.tipo = 'professor';


-- =========================================================
-- 13. GARANTIR PROGRESSO DO ALUNO NOS JOGOS
-- =========================================================

INSERT IGNORE INTO progresso
(usuario_id, jogo_id, dificuldade, status, porcentagem)
SELECT
    u.id,
    j.id,
    'facil',
    'disponivel',
    0
FROM usuarios u
CROSS JOIN jogos j
WHERE u.email = 'aluno@mathplay.com';


-- =========================================================
-- FIM DAS MODIFICAÇÕES
-- =========================================================