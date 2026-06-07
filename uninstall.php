<?php
/**
 * Uninstall-Routine fuer Widerrufsbutton für WooCommerce.
 *
 * Wird von WordPress ausgefuehrt, wenn das Plugin ueber das Backend
 * geloescht wird (nicht bei reiner Deaktivierung).
 *
 * WICHTIG (rechtliche Aufbewahrung):
 * Custom Table und Options werden NUR entfernt, wenn das Setting
 * "Daten bei Deinstallation löschen" aktiv ist. Default ist NICHT loeschen,
 * da Widerrufsdaten Aufbewahrungspflichten unterliegen koennen.
 *
 * @package Entruencer\Widerruf
 */

declare(strict_types=1);

// Nur durch WordPress-Uninstall-Prozess aufrufbar.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$settings = get_option('wrb_settings', []);
$delete   = is_array($settings) && !empty($settings['delete_data_on_uninstall']);

// Default-Verhalten: NICHTS loeschen (rechtliche Aufbewahrung).
if (!$delete) {
    return;
}

global $wpdb;

// Custom Table droppen.
$table = $wpdb->prefix . 'entruencer_withdrawals';
$wpdb->query("DROP TABLE IF EXISTS {$table}"); // phpcs:ignore WordPress.DB -- Tabellenname intern, kein User-Input.

// Plugin-Options entfernen.
delete_option('wrb_settings');
delete_option('wrb_schema_version');
delete_option('wrb_setup_version');

// Die automatisch angelegte Widerruf-Seite wird bewusst NICHT geloescht
// (Nutzerinhalt; kann manuell entfernt werden).
