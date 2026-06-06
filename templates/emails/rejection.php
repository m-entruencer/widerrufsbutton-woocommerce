<?php
/**
 * Mail-Template: Ablehnung des Widerrufs (Fall B/C, white-label).
 *
 * ENTWURF. Versand erst nach manueller 1-Klick-Freigabe im Admin.
 * Keine automatische Ablehnung als Default (Rechtsklaerung offen).
 *
 * Ueberschreibbar via Theme: <theme>/widerrufsbutton-wc/emails/rejection.php
 *
 * Verfuegbare Variablen (vom Mailer gesetzt, TODO):
 *  - string $brand_name
 *  - string $reason  Ablehnungsgrund (z.B. ausgeschlossenes Produkt / ausserhalb Frist).
 *
 * @package Entruencer\Widerruf
 */

if (!defined('ABSPATH')) {
    exit;
}
$brand_name = $brand_name ?? '';
$reason     = $reason ?? '';
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

<p><?php echo esc_html($brand_name); ?></p>
