
(() => {
    const chave = "mathplay-tema";

    function aplicarTema(tema) {
        document.documentElement.dataset.tema = tema;

        document.querySelectorAll("[data-alternar-tema]").forEach(botao => {
            const escuro = tema === "escuro";

            const icone = botao.querySelector(".icone-tema");
            if (icone) {
                icone.textContent = escuro ? "☀️" : "🌙";
            } else {
                botao.firstChild.textContent = escuro ? "☀️ " : "🌙 ";
            }

            botao.setAttribute(
                "aria-label",
                escuro ? "Ativar tema claro" : "Ativar tema escuro"
            );
        });
    }

    let temaSalvo = localStorage.getItem(chave);

    if (!["claro", "escuro"].includes(temaSalvo)) {
        temaSalvo = "claro";
    }

    aplicarTema(temaSalvo);

    document.addEventListener("click", evento => {
        const botao = evento.target.closest("[data-alternar-tema]");
        if (!botao) return;

        const atual = document.documentElement.dataset.tema;
        const novo = atual === "escuro" ? "claro" : "escuro";

        localStorage.setItem(chave, novo);
        aplicarTema(novo);
    });
})();
