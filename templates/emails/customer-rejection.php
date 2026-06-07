<?php
/**
 * WC-Email-Template (HTML): Ablehnung des Widerrufs an den Kunden.
 *
 * ENTWURF. Versand erst nach manueller 1-Klick-Freigabe im Admin.
 *
 * Ueberschreibbar via Theme: <theme>/woocommerce/emails/customer-rejection.php
 *
 * Verfuegbare Variablen:
 *  - string $reason         Ablehnungsgrund (optional).
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

<p><?php esc_html_e('nach Prüfung können wir deinem Widerruf nicht entsprechen.', 'widerrufsbutton-wc'); ?></p>

<?php if ($reason !== '') : ?>
<p>
<?php
/* translators: %s = Ablehnungsgrund. */
printf(esc_html__('Grund: %s', 'widerrufsbutton-wc'), esc_html($reason));
?>
</p>
<?php endif; ?>
<?php
do_action('woocommerce_email_footer', $email);
