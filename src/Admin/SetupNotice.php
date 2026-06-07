<?php
/**
 * Admin-Hinweise fuer Ersteinrichtung, Mailzustellung und Migration.
 *
 * @package Entruencer\Widerruf
 */

declare(strict_types=1);

namespace Entruencer\Widerruf\Admin;

use Entruencer\Widerruf\Frontend\Form;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Nimmt einen Laien beim Setup an die Hand:
 *  - Hinweis, falls keine Widerruf-Seite mit Shortcode existiert.
 *  - SMTP-Empfehlung, falls kein bekanntes Mailversand-Plugin aktiv ist.
 *  - Einmaliger Migrationshinweis fuer 0.1.0-Upgrader (Mail jetzt ueber WC).
 * SMTP- und Migrationshinweis sind dauerhaft ausblendbar (User-Meta).
 */
final class SetupNotice
{
    private const CAP = 'manage_woocommerce';

    /**
     * Bekannte SMTP-/Mailversand-Plugins (Plugin-Hauptdateien).
     */
    private const SMTP_PLUGINS = [
        'wp-mail-smtp/wp_mail_smtp.php',
        'post-smtp/postman-smtp.php',
        'easy-wp-smtp/easy-wp-smtp.php',
        'fluent-smtp/fluent-smtp.php',
    ];

    /**
     * Haengt Rendering + Dismiss-Handler ein.
     */
    public function register(): void
    {
        add_action('admin_notices', [$this, 'render']);
        add_action('admin_init', [$this, 'maybe_dismiss']);
    }

    /**
     * Verarbeitet das Ausblenden eines Hinweises (User-Meta).
     */
    public function maybe_dismiss(): void
    {
        if (!isset($_GET['wrb_dismiss']) || !current_user_can(self::CAP)) {
            return;
        }

        $key = sanitize_key(wp_unslash($_GET['wrb_dismiss']));
        if (!in_array($key, ['smtp', 'migration'], true)) {
            return;
        }

        check_admin_referer('wrb_dismiss_' . $key);

        update_user_meta(get_current_user_id(), 'wrb_dismissed_' . $key, 1);

        wp_safe_redirect(remove_query_arg(['wrb_dismiss', '_wpnonce']));
        exit;
    }

    /**
     * Rendert die relevanten Hinweise.
     */
    public function render(): void
    {
        if (!current_user_can(self::CAP)) {
            return;
        }

        $this->render_setup_notice();
        $this->render_smtp_notice();
        $this->render_migration_notice();
    }

    /**
     * Hinweis, wenn keine gueltige Widerruf-Seite existiert (loest sich selbst auf).
     */
    private function render_setup_notice(): void
    {
        $settings = Settings::all();
        $pageId   = (int) ($settings['withdrawal_page_id'] ?? 0);
        $post     = $pageId > 0 ? get_post($pageId) : null;
        $ok       = $post instanceof \WP_Post
            && $post->post_status === 'publish'
            && has_shortcode((string) $post->post_content, Form::SHORTCODE);

        if ($ok) {
            return;
        }

        $settingsUrl = admin_url('admin.php?page=wrb-withdrawals&tab=settings');

        echo '<div class="notice notice-warning"><p><strong>'
            . esc_html__('Widerrufsbutton: Einrichtung unvollständig.', 'widerrufsbutton-wc')
            . '</strong> '
            . sprintf(
                /* translators: 1: Shortcode, 2: Link zu den Einstellungen. */
                esc_html__('Es wurde keine veröffentlichte Seite mit dem Shortcode %1$s gefunden. Lege eine Seite mit dem Shortcode an oder prüfe die %2$s.', 'widerrufsbutton-wc'),
                '<code>[' . esc_html(Form::SHORTCODE) . ']</code>',
                '<a href="' . esc_url($settingsUrl) . '">' . esc_html__('Einstellungen', 'widerrufsbutton-wc') . '</a>'
            )
            . '</p></div>';
    }

    /**
     * SMTP-Empfehlung, falls kein bekanntes Mailversand-Plugin aktiv ist.
     */
    private function render_smtp_notice(): void
    {
        if (get_user_meta(get_current_user_id(), 'wrb_dismissed_smtp', true)) {
            return;
        }

        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        foreach (self::SMTP_PLUGINS as $plugin) {
            if (is_plugin_active($plugin)) {
                return;
            }
        }

        echo '<div class="notice notice-info"><p>'
            . esc_html__('Widerrufsbutton: Für eine zuverlässige Zustellung der Widerruf-Mails (gesetzliche Eingangsbestätigung) wird ein SMTP-/Mailversand-Plugin empfohlen, z.B. WP Mail SMTP oder FluentSMTP. Ohne sauberen Versand können Mails im Spam landen.', 'widerrufsbutton-wc')
            . ' <a href="' . esc_url($this->dismiss_url('smtp')) . '">'
            . esc_html__('Hinweis ausblenden', 'widerrufsbutton-wc')
            . '</a></p></div>';
    }

    /**
     * Einmaliger Hinweis fuer Upgrader: Mail-Einstellungen jetzt ueber WooCommerce.
     */
    private function render_migration_notice(): void
    {
        if (get_user_meta(get_current_user_id(), 'wrb_dismissed_migration', true)) {
            return;
        }

        // Nur zeigen, wenn alte Mail-Werte aus 0.1.0 noch in der Option liegen.
        $raw = get_option(Settings::OPTION, []);
        $raw = is_array($raw) ? $raw : [];

        $legacyKeys = ['sender_email', 'sender_name', 'body_acknowledgement', 'body_acceptance', 'body_rejection', 'subject_acknowledgement'];
        $hasLegacy  = false;
        foreach ($legacyKeys as $k) {
            if (!empty($raw[$k])) {
                $hasLegacy = true;
                break;
            }
        }

        if (!$hasLegacy) {
            return;
        }

        $emailUrl = admin_url('admin.php?page=wc-settings&tab=email');

        echo '<div class="notice notice-info"><p>'
            . sprintf(
                /* translators: %s = Link zu den WooCommerce-E-Mail-Einstellungen. */
                esc_html__('Widerrufsbutton: Die Mail-Einstellungen laufen ab Version 0.2.0 zentral über %s. Deine alten Plugin-Mailtexte werden nicht mehr verwendet.', 'widerrufsbutton-wc'),
                '<a href="' . esc_url($emailUrl) . '">' . esc_html__('WooCommerce -> Einstellungen -> E-Mails', 'widerrufsbutton-wc') . '</a>'
            )
            . ' <a href="' . esc_url($this->dismiss_url('migration')) . '">'
            . esc_html__('Verstanden', 'widerrufsbutton-wc')
            . '</a></p></div>';
    }

    /**
     * Baut eine nonce-gesicherte Dismiss-URL fuer den aktuellen Screen.
     */
    private function dismiss_url(string $key): string
    {
        return wp_nonce_url(add_query_arg('wrb_dismiss', $key), 'wrb_dismiss_' . $key);
    }
}
