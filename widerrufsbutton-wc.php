<?php
/**
 * Plugin Name:       Widerrufsbutton für WooCommerce
 * Plugin URI:        https://entruencer.de/
 * Description:       White-Label-Plugin der Entruencer UG fuer den ab 19.06.2026 gesetzlich verpflichtenden elektronischen Widerrufsbutton (EU-RL 2023/2673, Paragraf 312k BGB) in WooCommerce. Zweistufiger Flow, automatische Eingangsbestaetigung, fallbasierte Bearbeitung (A/B/C).
 * Version:           0.2.1
 * Author:            Entruencer UG (haftungsbeschraenkt)
 * Author URI:        https://entruencer.de/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       widerrufsbutton-wc
 * Domain Path:       /languages
 * Requires PHP:      8.1
 * Requires at least: 6.4
 * WC requires at least: 8.0
 *
 * @package Entruencer\Widerruf
 */

declare(strict_types=1);

namespace Entruencer\Widerruf;

// Direktaufruf verhindern.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin-Konstanten.
 *
 * WRB = WideRrufsButton.
 */
define('WRB_VERSION', '0.2.1');
define('WRB_PLUGIN_FILE', __FILE__);
define('WRB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WRB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WRB_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Autoloader laden (PSR-4, Namespace Entruencer\Widerruf -> src/).
 *
 * Bevorzugt der Composer-Autoloader (falls `composer install` lief).
 * Fehlt vendor/autoload.php (Standardfall beim Deploy aus dem ZIP), wird ein
 * schlanker PSR-4-Fallback-Autoloader registriert. So laeuft das Plugin
 * out-of-the-box ohne Composer-Schritt.
 */
$wrb_autoload = WRB_PLUGIN_DIR . 'vendor/autoload.php';
if (file_exists($wrb_autoload)) {
    require_once $wrb_autoload;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix  = 'Entruencer\\Widerruf\\';
        $baseDir = WRB_PLUGIN_DIR . 'src/';

        if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $file     = $baseDir . str_replace('\\', '/', $relative) . '.php';

        if (is_readable($file)) {
            require_once $file;
        }
    });
}

/**
 * HPOS-Kompatibilitaet (High-Performance Order Storage / Custom Order Tables)
 * deklarieren. Pflicht: Bestellzugriff niemals direkt auf wp_posts/postmeta,
 * nur via wc_get_order() / $order->get_meta().
 */
add_action('before_woocommerce_init', static function (): void {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            WRB_PLUGIN_FILE,
            true
        );
    }
});

/**
 * Aktivierungs-Hook -> Install\Migrator::activate().
 * Legt Custom Table an und setzt Schema-Versionsflag.
 */
register_activation_hook(__FILE__, static function (): void {
    Install\Migrator::activate();
});

/**
 * Deaktivierungs-Hook -> Install\Migrator::deactivate().
 * Entfernt KEINE Daten (rechtliche Aufbewahrung). Nur Cleanup von Transients/Cron.
 */
register_deactivation_hook(__FILE__, static function (): void {
    Install\Migrator::deactivate();
});

/**
 * Bootstrap der Plugin-Hauptklasse nach Laden aller Plugins.
 *
 * Guard: WooCommerce muss aktiv sein.
 */
add_action('plugins_loaded', static function (): void {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', static function (): void {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__(
                'Widerrufsbutton für WooCommerce benötigt ein aktives WooCommerce.',
                'widerrufsbutton-wc'
            );
            echo '</p></div>';
        });
        return;
    }

    Plugin::instance()->register();
});
