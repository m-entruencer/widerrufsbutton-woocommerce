<?php
/**
 * WC-Email-Template (HTML): Benachrichtigung an den Shop-Betreiber bei neuem Widerruf.
 *
 * Ueberschreibbar via Theme: <theme>/woocommerce/emails/admin-new-withdrawal.php
 *
 * Verfuegbare Variablen:
 *  - string $reference, $datum, $uhrzeit, $customer_name, $order_number
 *  - string $case_type, $case_hint, $status_label
 *  - string $detail_url     Direktlink zur Detailansicht im Backend.
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
<p><?php esc_html_e('Es ist ein neuer Widerruf eingegangen. Bitte pruefe den Vorgang und gib die Entscheidung im Backend frei.', 'widerrufsbutton-wc'); ?></p>

<table cellspacing="0" cellpadding="6" style="width:100%; border:1px solid #e5e5e5;" border="1">
    <tbody>
        <tr><th align="left"><?php esc_html_e('Referenz', 'widerrufsbutton-wc'); ?></th><td><?php echo esc_html($reference); ?></td></tr>
        <tr><th align="left"><?php esc_html_e('Eingang', 'widerrufsbutton-wc'); ?></th><td><?php echo esc_html(trim($datum . ' ' . $uhrzeit)); ?></td></tr>
        <tr><th align="left"><?php esc_html_e('Name', 'widerrufsbutton-wc'); ?></th><td><?php echo esc_html($customer_name); ?></td></tr>
        <tr><th align="left"><?php esc_html_e('Bestellnummer', 'widerrufsbutton-wc'); ?></th><td><?php echo esc_html($order_number); ?></td></tr>
        <tr><th align="left"><?php esc_html_e('Fall', 'widerrufsbutton-wc'); ?></th><td><?php echo esc_html(trim($case_type . ' ' . $case_hint)); ?></td></tr>
        <tr><th align="left"><?php esc_html_e('Status', 'widerrufsbutton-wc'); ?></th><td><?php echo esc_html($status_label); ?></td></tr>
    </tbody>
</table>

<?php if ($detail_url !== '') : ?>
<p style="margin-top:16px;">
    <a href="<?php echo esc_url($detail_url); ?>" style="display:inline-block; padding:10px 18px; background:#2271b1; color:#ffffff; text-decoration:none; border-radius:4px;">
        <?php esc_html_e('Vorgang im Backend oeffnen', 'widerrufsbutton-wc'); ?>
    </a>
</p>
<?php endif; ?>
<?php
do_action('woocommerce_email_footer', $email);
