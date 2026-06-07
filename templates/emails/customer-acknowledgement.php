<?php
/**
 * WC-Email-Template (HTML): Eingangsbestaetigung an den Kunden.
 *
 * Neutrale, gesetzlich verpflichtende Eingangsbestaetigung.
 * Datum UND Uhrzeit sind Pflichtbestandteil.
 *
 * Ueberschreibbar via Theme: <theme>/woocommerce/emails/customer-acknowledgement.php
 *
 * Verfuegbare Variablen:
 *  - string $datum, $uhrzeit  Eingangszeitpunkt (lokalisiert).
 *  - string $reference        Neutrale Vorgangsreferenz (z.B. WRB-123).
 *  - string $email_heading    Ueberschrift aus den WC-Mail-Einstellungen.
 *  - WC_Email $email          Die Mail-Instanz (fuer Header/Footer-Hooks).
 *
 * @package Entruencer\Widerruf
 */

if (!defined('ABSPATH')) {
    exit;
}

do_action('woocommerce_email_header', $email_heading, $email);
?>
<p><?php esc_html_e('Guten Tag,', 'widerrufsbutton-wc'); ?></p>

<p>
<?php
/* translators: %1$s = Datum, %2$s = Uhrzeit. */
printf(
    esc_html__('wir haben deinen Widerruf am %1$s um %2$s erhalten.', 'widerrufsbutton-wc'),
    esc_html($datum),
    esc_html($uhrzeit)
);
?>
</p>

<p><?php esc_html_e('Diese Nachricht bestätigt ausschließlich den Eingang deiner Erklärung.', 'widerrufsbutton-wc'); ?></p>

<?php if ($reference !== '') : ?>
<p><?php printf(esc_html__('Vorgangsreferenz: %s', 'widerrufsbutton-wc'), esc_html($reference)); ?></p>
<?php endif; ?>
<?php
do_action('woocommerce_email_footer', $email);
