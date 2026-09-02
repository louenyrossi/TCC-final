const formulario = document.getElementById("formLogin");

formulario.addEventListener("submit", function(event) {

    const email = document.getElementById("email").value;
    const senha = document.getElementById("senha").value;

    if (email == "" || senha == "") {

        event.preventDefault();

        alert("Preencha todos os campos.");

    }
});