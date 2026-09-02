```php
<?php

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$nome = $_SESSION["nome"];
$nivel = $_SESSION["nivel"];
$xp = $_SESSION["xp"];

echo "LOGIN FUNCIONOU!";
exit;
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Início | MathPlay</title>

    <link rel="stylesheet" href="css/inicio.css">

</head>

<body>

    <header class="cabecalho">

        <div class="logo">

            <span>✦</span>

            <h1>MathPlay</h1>

        </div>

        <a href="sair.php" class="sair">
            Sair
        </a>

    </header>


    <main class="pagina">

        <section class="boas-vindas">

            <p class="pequeno">
                Olá! 👋
            </p>

            <h2>
                Bem-vindo, <?php echo htmlspecialchars($nome); ?>!
            </h2>

            <p>
                Escolha um jogo e continue aprendendo matemática.
            </p>

        </section>


        <section class="informacoes">

            <div class="info-card">

                <span>⭐</span>

                <div>
                    <p>Nível</p>

                    <strong>
                        <?php echo $nivel; ?>
                    </strong>
                </div>

            </div>


            <div class="info-card">

                <span>⚡</span>

                <div>
                    <p>XP</p>

                    <strong>
                        <?php echo $xp; ?>
                    </strong>
                </div>

            </div>

        </section>


        <section class="jogos">

            <div class="titulo-secao">

                <h2>🎮 Seus jogos</h2>

                <p>
                    Pratique matemática se divertindo!
                </p>

            </div>


            <div class="lista-jogos">


                <article class="jogo-card">

                    <div class="imagem-jogo mercado">
                        🛒
                    </div>

                    <div class="jogo-conteudo">

                        <h3>Caixa Matemático</h3>

                        <p>
                            Seja o caixa do mercado! Calcule o valor
                            das compras e dê o troco corretamente.
                        </p>

                        <a href="#" class="botao">
                            Jogar
                        </a>

                    </div>

                </article>


                <article class="jogo-card">

                    <div class="imagem-jogo memoria">
                        🧠
                    </div>

                    <div class="jogo-conteudo">

                        <h3>Memória Matemática</h3>

                        <p>
                            Encontre os pares entre operações
                            matemáticas e seus resultados.
                        </p>

                        <a href="#" class="botao">
                            Jogar
                        </a>

                    </div>

                </article>


            </div>

        </section>

    </main>


    <script src="js/inicio.js"></script>

</body>

</html>
