const formulario = document.getElementById("formCadastro");
const mensagem = document.getElementById("mensagem");

formulario.addEventListener("submit", function(event) {

    const senha = document.getElementById("senha").value;
    const confirmarSenha = document.getElementById("confirmar_senha").value;

    if (senha !== confirmarSenha) {
        event.preventDefault();
        mensagem.textContent = "As senhas não são iguais.";
        mensagem.style.color = "#e74c3c";
    }

});