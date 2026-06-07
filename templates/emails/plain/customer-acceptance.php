<?php
/**
 * WC-Email-Template (Plain): Akzeptanz des Widerrufs an den Kunden.
 *
 * Ueberschreibbar via Theme: <theme>/woocommerce/emails/plain/customer-acceptance.php
 *
 * @package Entruencer\Widerruf
 */

if (!defined('ABSPATH')) {
    exit;
}

echo "= " . esc_html(wp_strip_all_tags($email_heading)) . " =\n\n";

echo esc_html__('Guten Tag,', 'widerrufsbutton-wc') . "\n\n";

echo esc_html__('wir bestätigen deinen Widerruf. Die Rückabwicklung wird eingeleitet.', 'widerrufsbutton-wc') . "\n\n";

if ($order_number !== '') {
    echo sprintf(esc_html__('Bestellnummer: %s', 'widerrufsbutton-wc'), esc_html($order_number)) . "\n\n";
}

echo "\n----------------------------------------\n\n";
echo wp_kses_post(apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text')));
