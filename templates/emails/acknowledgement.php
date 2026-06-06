<?php
/**
 * Mail-Template: Eingangsbestaetigung (neutral, white-label).
 *
 * Gesetzliche Pflicht. Geht in ALLEN Faellen sofort raus.
 * Datum UND Uhrzeit sind Pflichtbestandteil.
 *
 * Ueberschreibbar via Theme: <theme>/widerrufsbutton-wc/emails/acknowledgement.php
 *
 * Verfuegbare Variablen (vom Mailer gesetzt, TODO):
 *  - string $brand_name  White-Label-Absendername.
 *  - string $datum       Eingangsdatum (lokalisiert).
 *  - string $uhrzeit     Eingangsuhrzeit (lokalisiert).
 *  - string $reference   Neutrale Vorgangsreferenz (KEIN Hinweis auf Bestell-Existenz).
 *
 * @package Entruencer\Widerruf
 */

if (!defined('ABSPATH')) {
    exit;
}

$brand_name = $brand_name ?? '';
$datum      = $datum ?? '';
$uhrzeit    = $uhrzeit ?? '';
$reference  = $reference ?? '';
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

<p><?php echo esc_html($brand_name); ?></p>
