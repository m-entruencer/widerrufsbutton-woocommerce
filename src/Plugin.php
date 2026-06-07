<?php
/**
 * Plugin-Hauptklasse (Bootstrap / Wiring).
 *
 * @package Entruencer\Widerruf
 */

declare(strict_types=1);

namespace Entruencer\Widerruf;

use Entruencer\Widerruf\Admin\Menu;
use Entruencer\Widerruf\Admin\Settings;
use Entruencer\Widerruf\Admin\SetupNotice;
use Entruencer\Widerruf\Frontend\Form;
use Entruencer\Widerruf\Mail\EmailManager;
use Entruencer\Widerruf\Product\ExclusionField;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Zentrale Verdrahtungsklasse.
 *
 * Singleton: instance() liefert die eine Plugin-Instanz, register()
 * haengt alle Subsysteme in die WordPress-/WooCommerce-Hooks.
 */
final class Plugin
{
    private static ?Plugin $instance = null;

    private function __construct()
    {
        // Bewusst leer. Verdrahtung erfolgt in register().
    }

    /**
     * Singleton-Zugriff.
     */
    public static function instance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Verdrahtet alle Subsysteme.
     *
     * Wird einmalig aus dem plugins_loaded-Hook aufgerufen.
     */
    public function register(): void
    {
        // i18n.
        add_action('init', [$this, 'load_textdomain']);

        // Schema-Upgrade-Check (falls Plugin-Update neue Tabellenversion bringt).
        add_action('admin_init', [Install\Migrator::class, 'maybe_upgrade']);

        // Subsysteme initialisieren. Jede Klasse haengt ihre eigenen Hooks.
        // TODO: ggf. Dependency-Container statt direkter Instanziierung.
        (new Settings())->register();
        (new Menu())->register();
        (new Form())->register();
        (new ExclusionField())->register();
        (new EmailManager())->register();
        (new SetupNotice())->register();
    }

    /**
     * Laedt die Uebersetzungen aus languages/.
     */
    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            'widerrufsbutton-wc',
            false,
            dirname(WRB_PLUGIN_BASENAME) . '/languages'
        );
    }
}
