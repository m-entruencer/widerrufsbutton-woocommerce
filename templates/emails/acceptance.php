<?php
/**
 * Mail-Template: Akzeptanz des Widerrufs (Fall A, white-label).
 *
 * Ueberschreibbar via Theme: <theme>/widerrufsbutton-wc/emails/acceptance.php
 *
 * Verfuegbare Variablen (vom Mailer gesetzt, TODO):
 *  - string $brand_name
 *  - string $order_number
 *  - string $customer_name
 *
 * @package Entruencer\Widerruf
 */

if (!defined('ABSPATH')) {
    exit;
}
$brand_name   = $brand_name ?? '';
$order_number = $order_number ?? '';
?>
<p><?php esc_html_e('Guten Tag,', 'widerrufsbutton-wc'); ?></p>

<p><?php esc_html_e('wir bestätigen deinen Widerruf. Die Rückabwicklung wird eingeleitet.', 'widerrufsbutton-wc'); ?></p>

<?php if ($order_number !== '') : ?>
<p><?php printf(esc_html__('Bestellnummer: %s', 'widerrufsbutton-wc'), esc_html($order_number)); ?></p>
<?php endif; ?>

<p><?php echo esc_html($brand_name); ?></p>
