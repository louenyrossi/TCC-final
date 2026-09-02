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