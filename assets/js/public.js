/*
 * Widerrufsbutton fuer WooCommerce - Frontend-Verhalten.
 *
 * - Stufe-1/Stufe-2-Umschaltung (Button -> Formular).
 * - Honeypot bleibt serverseitig massgeblich; clientseitig nur Komfort.
 */
(function () {
    'use strict';

    function init(widget) {
        var openBtn = widget.querySelector('[data-wrb-action="open-form"]');
        var step1 = widget.querySelector('[data-wrb-step="1"]');
        var step2 = widget.querySelector('[data-wrb-step="2"]');

        if (!openBtn || !step1 || !step2) {
            return;
        }

        openBtn.addEventListener('click', function () {
            step1.hidden = true;
            step2.hidden = false;

            // Fokus auf das erste Eingabefeld (a11y).
            var firstField = step2.querySelector('input:not([type="hidden"]):not([tabindex="-1"])');
            if (firstField) {
                firstField.focus();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.wrb-widget').forEach(init);
    });
})();
