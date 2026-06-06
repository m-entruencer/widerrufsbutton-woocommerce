<?php
/**
 * Frontend-Template: zweistufige Widerruf-Form (neutral, white-label).
 *
 * Stufe 1: Button "Vertrag widerrufen".
 * Stufe 2: Formular (Name, Bestellnummer, E-Mail) + "Widerruf bestätigen".
 *
 * Ueberschreibbar via Theme: <theme>/widerrufsbutton-wc/frontend/form.php
 *
 * Sicherheit: Nonce-Feld + Honeypot-Feld (.wrb-form__hp) sind Pflicht.
 * BEM-artige Klassen mit Prefix wrb-. Styles ueber assets/css/public.css
 * und die White-Label-Custom-Properties (--wrb-*).
 *
 * @package Entruencer\Widerruf
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrb-widget">

    <!-- Stufe 1 -->
    <div class="wrb-step wrb-step--1" data-wrb-step="1">
        <button type="button" class="wrb-button wrb-button--primary" data-wrb-action="open-form">
            <?php esc_html_e('Vertrag widerrufen', 'widerrufsbutton-wc'); ?>
        </button>
    </div>

    <!-- Stufe 2 -->
    <div class="wrb-step wrb-step--2" data-wrb-step="2" hidden>
        <form class="wrb-form" method="post"
              action="<?php echo esc_url(admin_url('admin-post.php')); ?>">

            <input type="hidden" name="action" value="wrb_submit" />
            <input type="hidden" name="wrb_redirect" value="<?php echo esc_url($redirect ?? home_url('/')); ?>" />
            <?php wp_nonce_field('wrb_submit', 'wrb_nonce'); ?>

            <p class="wrb-form__row">
                <label class="wrb-form__label" for="wrb-name">
                    <?php esc_html_e('Name', 'widerrufsbutton-wc'); ?>
                </label>
                <input class="wrb-form__input" type="text" id="wrb-name" name="wrb_name" required />
            </p>

            <p class="wrb-form__row">
                <label class="wrb-form__label" for="wrb-order">
                    <?php esc_html_e('Bestellnummer', 'widerrufsbutton-wc'); ?>
                </label>
                <input class="wrb-form__input" type="text" id="wrb-order" name="wrb_order_number" required />
            </p>

            <p class="wrb-form__row">
                <label class="wrb-form__label" for="wrb-email">
                    <?php esc_html_e('E-Mail', 'widerrufsbutton-wc'); ?>
                </label>
                <input class="wrb-form__input" type="email" id="wrb-email" name="wrb_email" required />
            </p>

            <!-- Honeypot: fuer Menschen unsichtbar, von Bots gern befuellt. -->
            <p class="wrb-form__hp" aria-hidden="true">
                <label for="wrb-website"><?php esc_html_e('Website', 'widerrufsbutton-wc'); ?></label>
                <input type="text" id="wrb-website" name="wrb_website" tabindex="-1" autocomplete="off" />
            </p>

            <p class="wrb-form__row">
                <button type="submit" class="wrb-button wrb-button--primary">
                    <?php esc_html_e('Widerruf bestätigen', 'widerrufsbutton-wc'); ?>
                </button>
            </p>
        </form>
    </div>

</div>
