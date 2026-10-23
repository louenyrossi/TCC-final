/* =====================================================
   MathPower — Acessibilidade global
   Arquivo: assets/js/acessibilidade.js
===================================================== */
(() => {
    'use strict';

    const root = document.documentElement;
    const toggle = document.getElementById('mp-accessibility-toggle');
    const panel = document.getElementById('mp-accessibility-panel');
    const close = document.getElementById('mp-accessibility-close');
    const reset = document.getElementById('mp-accessibility-reset');

    if (!toggle || !panel || !close || !reset || toggle.dataset.ready === 'true') {
        return;
    }
    toggle.dataset.ready = 'true';

    const storageKey = 'mathpower-accessibility-v1';
    const defaults = {
        textSize: 0,
        contrast: false,
        font: false,
        spacing: false,
        links: false,
        motion: false
    };

    let settings = { ...defaults };

    try {
        const saved = JSON.parse(localStorage.getItem(storageKey));
        if (saved && typeof saved === 'object') {
            settings = { ...defaults, ...saved };
            settings.textSize = Math.max(0, Math.min(2, Number(settings.textSize) || 0));
            ['contrast', 'font', 'spacing', 'links', 'motion'].forEach(key => {
                settings[key] = Boolean(settings[key]);
            });
        }
    } catch (_) {
        // O painel ainda funciona se o armazenamento estiver indisponível.
    }

    function save() {
        try {
            localStorage.setItem(storageKey, JSON.stringify(settings));
        } catch (_) {
            // Não interrompe a página se o navegador bloquear o armazenamento.
        }
    }

    function applySettings() {
        root.classList.toggle('mp-a11y-text-large', settings.textSize === 1);
        root.classList.toggle('mp-a11y-text-xlarge', settings.textSize === 2);
        root.classList.toggle('mp-a11y-high-contrast', settings.contrast);
        root.classList.toggle('mp-a11y-readable-font', settings.font);
        root.classList.toggle('mp-a11y-spacing', settings.spacing);
        root.classList.toggle('mp-a11y-underline-links', settings.links);
        root.classList.toggle('mp-a11y-reduce-motion', settings.motion);

        panel.querySelectorAll('[data-mp-access-action]').forEach(button => {
            const action = button.dataset.mpAccessAction;
            if (['contrast', 'font', 'spacing', 'links', 'motion'].includes(action)) {
                button.setAttribute('aria-pressed', String(settings[action]));
            }
        });
    }

    function openPanel() {
        panel.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        close.focus();
    }

    function closePanel() {
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
    }

    toggle.addEventListener('click', () => {
        if (panel.hidden) openPanel();
        else closePanel();
    });

    close.addEventListener('click', closePanel);

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !panel.hidden) closePanel();
    });

    panel.addEventListener('click', event => {
        const button = event.target.closest('[data-mp-access-action]');
        if (!button) return;

        switch (button.dataset.mpAccessAction) {
            case 'increase':
                settings.textSize = Math.min(2, settings.textSize + 1);
                break;
            case 'decrease':
                settings.textSize = Math.max(0, settings.textSize - 1);
                break;
            case 'contrast':
                settings.contrast = !settings.contrast;
                break;
            case 'font':
                settings.font = !settings.font;
                break;
            case 'spacing':
                settings.spacing = !settings.spacing;
                break;
            case 'links':
                settings.links = !settings.links;
                break;
            case 'motion':
                settings.motion = !settings.motion;
                break;
        }

        applySettings();
        save();
    });

    reset.addEventListener('click', () => {
        settings = { ...defaults };
        applySettings();
        save();
    });

    applySettings();
})();
