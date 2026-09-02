<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro | MathPlay</title>
    <link rel="stylesheet" href="css/cadastro.css">

</head>

<body>

    <main class="pagina">

        <section class="cadastro">
            <div class="logo">
                <span>✦</span>
                <h1>MathPlay</h1>
            </div>

            <div class="titulo">

                <h2>Crie sua conta</h2>
                <p>
                    Aprenda matemática enquanto se diverte!
                </p>
            </div>

            <form action="cadastro.php" method="POST" id="formCadastro">
                <div class="campo">
                    <label for="nome">Nome</label>
                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        placeholder="Digite seu nome"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="email">E-mail</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Digite seu e-mail"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="senha">Senha</label>
                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Digite sua senha"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="confirmar_senha">
                        Confirmar senha
                    </label>

                    <input
                        type="password"
                        id="confirmar_senha"
                        name="confirmar_senha"
                        placeholder="Digite a senha novamente"
                        required
                    >
                </div>

                <button type="submit">
                    Criar minha conta
                </button>

            </form>
            <p id="mensagem"></p>
            <p class="login">
                Já possui uma conta?
                <a href="login.php">
                    Entrar
                </a>
            </p>

        </section>

    </main>
    
    <script src="js/cadastro.js"></script>

</body>

</html>