<?php
/**
 * WC-Email-Template (Plain): Benachrichtigung an den Shop-Betreiber bei neuem Widerruf.
 *
 * Ueberschreibbar via Theme: <theme>/woocommerce/emails/plain/admin-new-withdrawal.php
 *
 * @package Entruencer\Widerruf
 */

if (!defined('ABSPATH')) {
    exit;
}

echo "= " . esc_html(wp_strip_all_tags($email_heading)) . " =\n\n";

echo esc_html__('Es ist ein neuer Widerruf eingegangen. Bitte pruefe den Vorgang und gib die Entscheidung im Backend frei.', 'widerrufsbutton-wc') . "\n\n";

echo esc_html__('Referenz', 'widerrufsbutton-wc') . ': ' . esc_html($reference) . "\n";
echo esc_html__('Eingang', 'widerrufsbutton-wc') . ': ' . esc_html(trim($datum . ' ' . $uhrzeit)) . "\n";
echo esc_html__('Name', 'widerrufsbutton-wc') . ': ' . esc_html($customer_name) . "\n";
echo esc_html__('Bestellnummer', 'widerrufsbutton-wc') . ': ' . esc_html($order_number) . "\n";
echo esc_html__('Fall', 'widerrufsbutton-wc') . ': ' . esc_html(trim($case_type . ' ' . $case_hint)) . "\n";
echo esc_html__('Status', 'widerrufsbutton-wc') . ': ' . esc_html($status_label) . "\n\n";

if ($detail_url !== '') {
    echo esc_html__('Vorgang im Backend oeffnen:', 'widerrufsbutton-wc') . "\n" . esc_url_raw($detail_url) . "\n\n";
}

echo "\n----------------------------------------\n\n";
echo wp_kses_post(apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text')));
