<?php
/**
 * MathPower - painel global de acessibilidade.
 * Inclua antes de </body> nas páginas PHP que possuem interface.
 */
if (defined('MATHPOWER_ACCESSIBILITY_INCLUDED')) {
    return;
}
define('MATHPOWER_ACCESSIBILITY_INCLUDED', true);
?>
<div id="mp-accessibility" class="mp-accessibility">
    <button
        id="mp-accessibility-toggle"
        class="mp-accessibility__toggle"
        type="button"
        aria-expanded="false"
        aria-controls="mp-accessibility-panel"
        aria-label="Abrir opções de acessibilidade"
    >
        <span aria-hidden="true">♿</span>
        <span>Acessibilidade</span>
    </button>

    <section
        id="mp-accessibility-panel"
        class="mp-accessibility__panel"
        aria-labelledby="mp-accessibility-title"
        hidden
    >
        <div class="mp-accessibility__header">
            <h2 id="mp-accessibility-title">Acessibilidade</h2>
            <button id="mp-accessibility-close" type="button" aria-label="Fechar painel">×</button>
        </div>

        <p class="mp-accessibility__description">
            Personalize a leitura e a visualização do MathPower.
        </p>

        <div class="mp-accessibility__font-controls">
            <button type="button" data-mp-access-action="decrease">A− Diminuir texto</button>
            <button type="button" data-mp-access-action="increase">A+ Aumentar texto</button>
        </div>

        <button type="button" class="mp-accessibility__option" data-mp-access-action="contrast" aria-pressed="false">Alto contraste</button>
        <button type="button" class="mp-accessibility__option" data-mp-access-action="font" aria-pressed="false">Fonte mais legível</button>
        <button type="button" class="mp-accessibility__option" data-mp-access-action="spacing" aria-pressed="false">Aumentar espaçamento do texto</button>
        <button type="button" class="mp-accessibility__option" data-mp-access-action="links" aria-pressed="false">Sublinhar links</button>
        <button type="button" class="mp-accessibility__option" data-mp-access-action="motion" aria-pressed="false">Reduzir animações</button>

        <button id="mp-accessibility-reset" class="mp-accessibility__reset" type="button">Restaurar padrão</button>
        <p class="mp-accessibility__note">Use Tab para navegar e Enter ou Espaço para ativar os controles.</p>
    </section>
</div>
