const botoesJogar = document.querySelectorAll(".botao");

botoesJogar.forEach(function(botao) {

    botao.addEventListener("click", function(event) {

        if (botao.getAttribute("href") == "#") {
            event.preventDefault();
            alert("Esse jogo ainda está sendo desenvolvido!");

        }

    });

});