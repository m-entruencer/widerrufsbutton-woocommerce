<?php
/**
 * WC-Email-Template (Plain): Ablehnung des Widerrufs an den Kunden.
 *
 * Ueberschreibbar via Theme: <theme>/woocommerce/emails/plain/customer-rejection.php
 *
 * @package Entruencer\Widerruf
 */

if (!defined('ABSPATH')) {
    exit;
}

echo "= " . esc_html(wp_strip_all_tags($email_heading)) . " =\n\n";

echo esc_html__('Guten Tag,', 'widerrufsbutton-wc') . "\n\n";

echo esc_html__('nach Prüfung können wir deinem Widerruf nicht entsprechen.', 'widerrufsbutton-wc') . "\n\n";

if ($reason !== '') {
    /* translators: %s = Ablehnungsgrund. */
    echo sprintf(esc_html__('Grund: %s', 'widerrufsbutton-wc'), esc_html($reason)) . "\n\n";
}

echo "\n----------------------------------------\n\n";
echo wp_kses_post(apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text')));
