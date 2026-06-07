<?php
/**
 * WC-Email-Template (Plain): Eingangsbestaetigung an den Kunden.
 *
 * Ueberschreibbar via Theme: <theme>/woocommerce/emails/plain/customer-acknowledgement.php
 *
 * @package Entruencer\Widerruf
 */

if (!defined('ABSPATH')) {
    exit;
}

echo "= " . esc_html(wp_strip_all_tags($email_heading)) . " =\n\n";

echo esc_html__('Guten Tag,', 'widerrufsbutton-wc') . "\n\n";

/* translators: %1$s = Datum, %2$s = Uhrzeit. */
echo sprintf(
    esc_html__('wir haben deinen Widerruf am %1$s um %2$s erhalten.', 'widerrufsbutton-wc'),
    esc_html($datum),
    esc_html($uhrzeit)
) . "\n\n";

echo esc_html__('Diese Nachricht bestätigt ausschließlich den Eingang deiner Erklärung.', 'widerrufsbutton-wc') . "\n\n";

if ($reference !== '') {
    echo sprintf(esc_html__('Vorgangsreferenz: %s', 'widerrufsbutton-wc'), esc_html($reference)) . "\n\n";
}

echo "\n----------------------------------------\n\n";
echo wp_kses_post(apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text')));
