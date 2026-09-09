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

USE b14_42774124_tcc_final;

UPDATE perguntas
SET
    enunciado = 'Calcule: 3/4 + 1/4',
    resposta_correta = '1',
    dificuldade = 'facil',
    pontuacao = 10,
    ano_serie = 6,
    conteudo = 'Frações'
WHERE id = 6
  AND jogo_id = 2;


UPDATE perguntas
SET
    enunciado = 'Calcule: 5/6 - 1/6',
    resposta_correta = '0.6666666667',
    dificuldade = 'facil',
    pontuacao = 10,
    ano_serie = 6,
    conteudo = 'Frações'
WHERE id = 7
  AND jogo_id = 2;


UPDATE perguntas
SET
    enunciado = 'Calcule: 2**2 + 3',
    resposta_correta = '7',
    dificuldade = 'facil',
    pontuacao = 10,
    ano_serie = 6,
    conteudo = 'Potenciação'
WHERE id = 8
  AND jogo_id = 2;


UPDATE perguntas
SET
    enunciado = 'Calcule: 18/3 + 4',
    resposta_correta = '10',
    dificuldade = 'facil',
    pontuacao = 10,
    ano_serie = 6,
    conteudo = 'Operações e Expressões'
WHERE id = 9
  AND jogo_id = 2;


UPDATE perguntas
SET
    enunciado = 'Calcule: 3**2 + 4**2',
    resposta_correta = '25',
    dificuldade = 'medio',
    pontuacao = 15,
    ano_serie = 7,
    conteudo = 'Potenciação'
WHERE id = 10
  AND jogo_id = 2;


UPDATE perguntas
SET
    enunciado = 'Calcule: 5/2 + 3/2',
    resposta_correta = '4',
    dificuldade = 'medio',
    pontuacao = 15,
    ano_serie = 8,
    conteudo = 'Frações'
WHERE id = 11
  AND jogo_id = 2;


UPDATE perguntas
SET
    enunciado = 'Calcule: 2**3 * 3 - 5',
    resposta_correta = '19',
    dificuldade = 'medio',
    pontuacao = 15,
    ano_serie = 8,
    conteudo = 'Expressões Numéricas'
WHERE id = 12
  AND jogo_id = 2;


UPDATE perguntas
SET
    enunciado = 'Calcule: (5/2)**2 - 3',
    resposta_correta = '3.25',
    dificuldade = 'dificil',
    pontuacao = 20,
    ano_serie = 9,
    conteudo = 'Frações e Potenciação'
WHERE id = 13
  AND jogo_id = 2;

  USE b14_42774124_tcc_final;

-- Garante as 8 turmas
INSERT INTO turmas (nome, ano_serie)
SELECT '6º A', 6
WHERE NOT EXISTS (SELECT 1 FROM turmas WHERE nome = '6º A');

INSERT INTO turmas (nome, ano_serie)
SELECT '6º B', 6
WHERE NOT EXISTS (SELECT 1 FROM turmas WHERE nome = '6º B');

INSERT INTO turmas (nome, ano_serie)
SELECT '7º A', 7
WHERE NOT EXISTS (SELECT 1 FROM turmas WHERE nome = '7º A');

INSERT INTO turmas (nome, ano_serie)
SELECT '7º B', 7
WHERE NOT EXISTS (SELECT 1 FROM turmas WHERE nome = '7º B');

INSERT INTO turmas (nome, ano_serie)
SELECT '8º A', 8
WHERE NOT EXISTS (SELECT 1 FROM turmas WHERE nome = '8º A');

INSERT INTO turmas (nome, ano_serie)
SELECT '8º B', 8
WHERE NOT EXISTS (SELECT 1 FROM turmas WHERE nome = '8º B');

INSERT INTO turmas (nome, ano_serie)
SELECT '9º A', 9
WHERE NOT EXISTS (SELECT 1 FROM turmas WHERE nome = '9º A');

INSERT INTO turmas (nome, ano_serie)
SELECT '9º B', 9
WHERE NOT EXISTS (SELECT 1 FROM turmas WHERE nome = '9º B');


-- Relação entre jogos e turmas
CREATE TABLE IF NOT EXISTS jogos_turmas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jogo_id INT NOT NULL,
    turma_id INT NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    FOREIGN KEY (jogo_id)
        REFERENCES jogos(id)
        ON DELETE CASCADE,

    FOREIGN KEY (turma_id)
        REFERENCES turmas(id)
        ON DELETE CASCADE,

    UNIQUE (jogo_id, turma_id)
);
USE b14_42774124_tcc_final;

INSERT INTO turmas (nome, ano_serie)
SELECT '6º A', 6
WHERE NOT EXISTS (SELECT 1 FROM turmas WHERE nome = '6º A');

INSERT INTO turmas (nome, ano_serie)
SELECT '6º B', 6
WHERE NOT EXISTS (SELECT 1 FROM turmas WHERE nome = '6º B');

INSERT INTO turmas (nome, ano_serie)
SELECT '7º A', 7
WHERE NOT EXISTS (SELECT 1 FROM turmas WHERE nome = '7º A');

INSERT INTO turmas (nome, ano_serie)
SELECT '7º B', 7
WHERE NOT EXISTS (SELECT 1 FROM turmas WHERE nome = '7º B');

INSERT INTO turmas (nome, ano_serie)
SELECT '8º A', 8
WHERE NOT EXISTS (SELECT 1 FROM turmas WHERE nome = '8º A');

INSERT INTO turmas (nome, ano_serie)
SELECT '8º B', 8
WHERE NOT EXISTS (SELECT 1 FROM turmas WHERE nome = '8º B');

INSERT INTO turmas (nome, ano_serie)
SELECT '9º A', 9
WHERE NOT EXISTS (SELECT 1 FROM turmas WHERE nome = '9º A');

INSERT INTO turmas (nome, ano_serie)
SELECT '9º B', 9
WHERE NOT EXISTS (SELECT 1 FROM turmas WHERE nome = '9º B');

USE b14_42774124_tcc_final;

INSERT INTO perguntas
(jogo_id, ano_serie, conteudo, enunciado, resposta_correta, dificuldade, pontuacao)
VALUES

(
    1,
    6,
    'Operações e porcentagem',
    'Ana foi ao mercado com R$ 50,00 para comprar alguns produtos. Ela gastou R$ 32,00 e depois encontrou uma promoção de 10% em um produto que custava R$ 20,00. Depois de comprar o produto com desconto, quanto dinheiro restou para Ana?',
    '16',
    'facil',
    10
),

(
    1,
    6,
    'Operações com dinheiro',
    'Pedro está juntando dinheiro para comprar uma bicicleta que custa R$ 200,00. Ele já conseguiu guardar R$ 80,00 e ganhou mais R$ 40,00 de sua família. Quanto ainda falta para Pedro comprar a bicicleta?',
    '80',
    'facil',
    10
),

(
    1,
    6,
    'Porcentagem',
    'Marina foi comprar um estojo que custava R$ 30,00. A papelaria ofereceu 20% de desconto e ela decidiu aproveitar a promoção. Qual será o valor que Marina pagará pelo estojo?',
    '24',
    'facil',
    10
),

(
    1,
    6,
    'Operações com dinheiro',
    'Lucas recebeu R$ 60,00 para comprar materiais escolares. Ele gastou R$ 25,00 em um caderno e R$ 15,00 em lápis e canetas. Quanto dinheiro Lucas ainda tem?',
    '20',
    'facil',
    10
),

(
    1,
    6,
    'Operações com dinheiro',
    'Sofia quer comprar um livro que custa R$ 50,00. Ela já possui R$ 30,00 e decidiu guardar R$ 5,00 por semana para completar o valor. Depois de quantas semanas Sofia terá dinheiro suficiente para comprar o livro?',
    '4',
    'facil',
    10
);

USE b14_42774124_tcc_final;

INSERT INTO perguntas
(jogo_id, ano_serie, conteudo, enunciado, resposta_correta, dificuldade, pontuacao)
VALUES

(
    1,
    7,
    'Porcentagem',
    'Uma mochila custa R$ 120,00 e está com 15% de desconto. Pedro tem R$ 110,00 para realizar a compra e quer saber se o dinheiro será suficiente. Qual será o preço da mochila após o desconto?',
    '102',
    'medio',
    15
),

(
    1,
    7,
    'Porcentagem',
    'Uma bicicleta custava R$ 500,00 no começo do ano. Depois de alguns meses, seu preço aumentou 10% por causa dos custos da loja. Qual passou a ser o preço da bicicleta?',
    '550',
    'medio',
    15
),

(
    1,
    7,
    'Operações com dinheiro',
    'Mariana levou R$ 150,00 para comprar uma camiseta e uma calça. A camiseta custou R$ 45,00 e a calça custou R$ 80,00. Quanto dinheiro sobrou depois das compras?',
    '25',
    'medio',
    15
),

(
    1,
    7,
    'Porcentagem',
    'João encontrou um tênis de R$ 200,00 com 20% de desconto. Ele tinha R$ 170,00 guardados e decidiu verificar se conseguiria comprar o tênis. Quanto ele pagará pelo tênis e quanto sobrará?',
    '160',
    'medio',
    15
),

(
    1,
    7,
    'Operações com dinheiro',
    'Lucas quer comprar um jogo que custa R$ 600,00. Ele já possui R$ 240,00 e pretende guardar R$ 60,00 todos os meses. Por quantos meses Lucas precisará guardar dinheiro para conseguir comprar o jogo?',
    '6',
    'medio',
    15
);

USE b14_42774124_tcc_final;

INSERT INTO perguntas
(jogo_id, ano_serie, conteudo, enunciado, resposta_correta, dificuldade, pontuacao)
VALUES

(
    1,
    8,
    'Porcentagem',
    'Uma bicicleta custa R$ 800,00 e está com 15% de desconto durante uma promoção. Carlos tem R$ 700,00 guardados e quer saber se conseguirá comprar a bicicleta com o desconto. Qual será o preço da bicicleta após o desconto?',
    '680',
    'dificil',
    20
),

(
    1,
    8,
    'Porcentagem',
    'Um celular custava R$ 1.200,00, mas seu preço aumentou 10% devido a mudanças nos custos da loja. Depois do aumento, a loja decidiu oferecer um desconto de 5% sobre o novo preço. Qual será o preço final do celular?',
    '1254',
    'dificil',
    20
),

(
    1,
    8,
    'Porcentagem e operações',
    'Uma família foi ao supermercado com R$ 250,00 para fazer suas compras. Ao final, gastou 60% desse valor e ainda comprou um produto de R$ 20,00 que não estava planejado. Quanto dinheiro restou para a família?',
    '80',
    'dificil',
    20
),

(
    1,
    8,
    'Porcentagem',
    'Uma turma pretende juntar R$ 1.200,00 para realizar um passeio. Depois de algumas semanas, os alunos já conseguiram juntar 65% desse valor. Quanto dinheiro ainda falta para alcançar o objetivo?',
    '420',
    'dificil',
    20
),

(
    1,
    8,
    'Porcentagem',
    'Uma loja aumentou o preço de uma televisão de R$ 2.000,00 em 10%. Na semana seguinte, anunciou um desconto de 10% sobre o novo preço. Qual será o preço final da televisão?',
    '1980',
    'dificil',
    20
);
USE b14_42774124_tcc_final;

INSERT INTO perguntas
(jogo_id, ano_serie, conteudo, enunciado, resposta_correta, dificuldade, pontuacao)
VALUES

(
    1,
    9,
    'Porcentagem',
    'Uma televisão custa R$ 2.500,00 em uma loja. Primeiro, o preço recebeu um aumento de 12% e, depois, a loja ofereceu um desconto de 10% sobre o novo valor. Qual será o preço final da televisão?',
    '2520',
    'dificil',
    25
),

(
    1,
    9,
    'Porcentagem',
    'Um celular custa R$ 1.800,00 à vista. Uma loja oferece 15% de desconto para quem pagar no dinheiro, mas João possui apenas R$ 1.500,00. Depois do desconto, quanto João ainda precisará conseguir para comprar o celular?',
    '30',
    'dificil',
    25
),

(
    1,
    9,
    'Porcentagem',
    'Uma bicicleta custava R$ 1.200,00. Durante uma promoção, seu preço foi reduzido em 20%. Na semana seguinte, a loja aumentou o novo preço em 10%. Qual será o preço da bicicleta depois dessas duas alterações?',
    '1056',
    'dificil',
    25
),

(
    1,
    9,
    'Porcentagem e operações',
    'Uma turma precisa juntar R$ 2.400,00 para realizar uma viagem. Na primeira etapa, conseguiu arrecadar 35% desse valor. Depois, arrecadou mais R$ 480,00 em uma campanha. Quanto ainda falta para a turma alcançar o valor necessário?',
    '1080',
    'dificil',
    25
),

(
    1,
    9,
    'Porcentagem',
    'Uma família separou R$ 3.000,00 para comprar alguns móveis. Durante as compras, conseguiu um desconto de 15% sobre o valor total. Depois, gastou mais R$ 300,00 comprando um item que não estava planejado. Considerando o desconto, quanto a família terá gasto ao todo?',
    '3150',
    'dificil',
    25
);
USE b14_42774124_tcc_final;

INSERT INTO perguntas
(jogo_id, ano_serie, conteudo, enunciado, resposta_correta, dificuldade, pontuacao)
VALUES

-- POTENCIAÇÃO
(2, 6, 'Potenciação', '2³', '8', 'facil', 10),
(2, 6, 'Potenciação', '5²', '25', 'facil', 10),

-- DIVISÃO COM VÍRGULA
(2, 6, 'Divisão com vírgula', '7,5 ÷ 3', '2,5', 'medio', 15),
(2, 6, 'Divisão com vírgula', '12,6 ÷ 6', '2,1', 'medio', 15),

-- ÂNGULOS
(2, 6, 'Ângulos', '90°', 'Reto', 'facil', 10),
(2, 6, 'Ângulos', '120°', 'Obtuso', 'medio', 15),

-- FRAÇÕES
(2, 6, 'Frações', '1/2 + 1/2', '1', 'facil', 10),
(2, 6, 'Frações', '1/4 + 1/4', '1/2', 'medio', 15),

-- PORCENTAGEM
(2, 6, 'Porcentagem', '10% de 50', '5', 'facil', 10),
(2, 6, 'Porcentagem', '25% de 100', '25', 'facil', 10);
