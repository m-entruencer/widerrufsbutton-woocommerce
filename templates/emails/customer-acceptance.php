<?php
/**
 * WC-Email-Template (HTML): Akzeptanz des Widerrufs an den Kunden.
 *
 * Versand erst nach manueller 1-Klick-Freigabe im Admin.
 *
 * Ueberschreibbar via Theme: <theme>/woocommerce/emails/customer-acceptance.php
 *
 * Verfuegbare Variablen:
 *  - string $order_number   Bestellnummer (optional).
 *  - string $reference      Vorgangsreferenz.
 *  - string $email_heading  Ueberschrift aus den WC-Mail-Einstellungen.
 *  - WC_Email $email        Die Mail-Instanz.
 *
 * @package Entruencer\Widerruf
 */

if (!defined('ABSPATH')) {
    exit;
}

do_action('woocommerce_email_header', $email_heading, $email);
?>
<p><?php esc_html_e('Guten Tag,', 'widerrufsbutton-wc'); ?></p>

<p><?php esc_html_e('wir bestätigen deinen Widerruf. Die Rückabwicklung wird eingeleitet.', 'widerrufsbutton-wc'); ?></p>

<?php if ($order_number !== '') : ?>
<p><?php printf(esc_html__('Bestellnummer: %s', 'widerrufsbutton-wc'), esc_html($order_number)); ?></p>
<?php endif; ?>
<?php
do_action('woocommerce_email_footer', $email);
