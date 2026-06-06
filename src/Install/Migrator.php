<?php
/**
 * Installations- und Migrations-Routinen.
 *
 * @package Entruencer\Widerruf
 */

declare(strict_types=1);

namespace Entruencer\Widerruf\Install;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Legt die Custom Table {$wpdb->prefix}entruencer_withdrawals an und
 * verwaltet die Schema-Version.
 */
final class Migrator
{
    /**
     * Aktuelle DB-Schema-Version. Bei Schemaaenderung erhoehen.
     */
    public const SCHEMA_VERSION = '1';

    /**
     * Option-Key fuer die gespeicherte Schema-Version.
     */
    public const SCHEMA_OPTION = 'wrb_schema_version';

    /**
     * Tabellen-Basisname (ohne wpdb-Prefix).
     */
    public const TABLE = 'entruencer_withdrawals';

    /**
     * Aktivierungs-Routine: Tabellen anlegen, Schema-Version setzen.
     */
    public static function activate(): void
    {
        self::create_tables();
        update_option(self::SCHEMA_OPTION, self::SCHEMA_VERSION);

        // TODO: Default-Settings anlegen, falls noch nicht vorhanden.
        // TODO: ggf. Rewrite-Rules fuer die oeffentliche Widerruf-Seite flushen.
    }

    /**
     * Deaktivierungs-Routine.
     *
     * Entfernt KEINE Widerrufsdaten (rechtliche Aufbewahrung).
     * Nur fluechtige Artefakte (Transients, Cron) duerfen hier weg.
     */
    public static function deactivate(): void
    {
        // TODO: geplante Cron-Events entfernen.
        // TODO: Rate-Limit-Transients aufraeumen.
    }

    /**
     * Prueft bei admin_init, ob ein Schema-Upgrade noetig ist.
     */
    public static function maybe_upgrade(): void
    {
        $installed = (string) get_option(self::SCHEMA_OPTION, '0');

        if (version_compare($installed, self::SCHEMA_VERSION, '<')) {
            self::create_tables();
            update_option(self::SCHEMA_OPTION, self::SCHEMA_VERSION);
            // TODO: versionsspezifische Daten-Migrationen ausfuehren.
        }
    }

    /**
     * Erstellt bzw. aktualisiert die Custom Table via dbDelta().
     *
     * Felder gemaess Spezifikation (siehe docs/architecture.md).
     */
    private static function create_tables(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table           = $wpdb->prefix . self::TABLE;
        $charset_collate = $wpdb->get_charset_collate();

        // dbDelta ist whitespace-/formatempfindlich: zwei Leerzeichen nach PRIMARY KEY,
        // jedes Feld auf eigener Zeile, KEY-Definitionen am Ende.
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT UNSIGNED NULL,
            order_number VARCHAR(64) NULL,
            customer_email VARCHAR(191) NULL,
            customer_name VARCHAR(191) NULL,
            received_at_utc DATETIME NULL,
            received_at_local DATETIME NULL,
            case_type CHAR(1) NULL,
            deadline_days_snapshot SMALLINT UNSIGNED NULL,
            order_date_snapshot DATETIME NULL,
            excluded_flag TINYINT(1) NOT NULL DEFAULT 0,
            exclusion_reason TEXT NULL,
            waiver_proven TINYINT(1) NOT NULL DEFAULT 0,
            confirmation_mail_sent TINYINT(1) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'eingegangen',
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY customer_email (customer_email),
            KEY status (status)
        ) {$charset_collate};";

        dbDelta($sql);

        // TODO: Ergebnis von dbDelta auswerten / Fehler loggen.
    }
}
