<?php
/**
 * Admin-Menue: WooCommerce-Submenue "Widerrufe".
 *
 * @package Entruencer\Widerruf
 */

declare(strict_types=1);

namespace Entruencer\Widerruf\Admin;

use Entruencer\Widerruf\Domain\CaseResolver;
use Entruencer\Widerruf\Mail\Mailer;
use Entruencer\Widerruf\Repository\WithdrawalRepository;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registriert das Backend-Menue, die Listen-/Detailansicht und die
 * 1-Klick-Freigabe der Entscheidungs-Entwuerfe (Akzeptanz/Ablehnung).
 */
final class Menu
{
    private const CAP   = 'manage_woocommerce';
    private const SLUG  = 'wrb-withdrawals';
    private const PER_PAGE = 20;

    /**
     * Haengt die Admin-Hooks ein.
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_post_wrb_release_draft', [$this, 'handle_release_draft']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Laedt admin.css nur auf dem Plugin-Screen.
     */
    public function enqueue_assets(string $hook): void
    {
        if (strpos($hook, self::SLUG) === false) {
            return;
        }

        wp_enqueue_style('wrb-admin', WRB_PLUGIN_URL . 'assets/css/admin.css', [], WRB_VERSION);
    }

    /**
     * Fuegt das Submenue unter WooCommerce hinzu.
     */
    public function add_menu(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Widerrufe', 'widerrufsbutton-wc'),
            __('Widerrufe', 'widerrufsbutton-wc'),
            self::CAP,
            self::SLUG,
            [$this, 'render_page']
        );
    }

    /**
     * Routet zwischen Liste, Detail und Settings.
     */
    public function render_page(): void
    {
        if (!current_user_can(self::CAP)) {
            wp_die(esc_html__('Keine Berechtigung.', 'widerrufsbutton-wc'));
        }

        $tab  = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'list';
        $view = isset($_GET['view']) ? (int) $_GET['view'] : 0;

        echo '<div class="wrap wrb-admin">';
        echo '<h1>' . esc_html__('Widerrufe', 'widerrufsbutton-wc') . '</h1>';

        $this->render_tabs($tab);
        $this->render_notice();

        if ($tab === 'settings') {
            (new Settings())->render();
        } elseif ($view > 0) {
            $this->render_detail($view);
        } else {
            $this->render_list();
        }

        echo '</div>';
    }

    /**
     * Tab-Navigation Liste / Einstellungen.
     */
    private function render_tabs(string $tab): void
    {
        $base = menu_page_url(self::SLUG, false);
        ?>
        <nav class="nav-tab-wrapper">
            <a href="<?php echo esc_url($base); ?>" class="nav-tab <?php echo $tab !== 'settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Liste', 'widerrufsbutton-wc'); ?></a>
            <a href="<?php echo esc_url(add_query_arg('tab', 'settings', $base)); ?>" class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Einstellungen', 'widerrufsbutton-wc'); ?></a>
        </nav>
        <?php
    }

    /**
     * Zeigt Erfolg/Fehler-Notices nach Aktionen.
     */
    private function render_notice(): void
    {
        if (!isset($_GET['wrb_msg'])) {
            return;
        }

        $msg = sanitize_key(wp_unslash($_GET['wrb_msg']));

        $map = [
            'sent'     => ['updated', __('Entscheidung versendet und Status aktualisiert.', 'widerrufsbutton-wc')],
            'failed'   => ['error', __('Versand fehlgeschlagen. Bitte Mail-Konfiguration pruefen.', 'widerrufsbutton-wc')],
            'invalid'  => ['error', __('Ungueltige Anfrage.', 'widerrufsbutton-wc')],
        ];

        if (!isset($map[$msg])) {
            return;
        }

        printf(
            '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
            $msg === 'sent' ? 'success' : 'error',
            esc_html($map[$msg][1])
        );
    }

    /**
     * Rendert die paginierte Liste.
     */
    private function render_list(): void
    {
        $repo   = new WithdrawalRepository();
        $status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $paged  = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;

        $args = [
            'status' => $status,
            'search' => $search,
            'limit'  => self::PER_PAGE,
            'offset' => ($paged - 1) * self::PER_PAGE,
        ];

        $rows  = $repo->list($args);
        $total = $repo->count($args);
        $base  = menu_page_url(self::SLUG, false);

        // Suche.
        ?>
        <form method="get" class="wrb-list-search">
            <input type="hidden" name="page" value="<?php echo esc_attr(self::SLUG); ?>" />
            <p class="search-box">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Name, E-Mail, Bestellnr.', 'widerrufsbutton-wc'); ?>" />
                <button class="button"><?php esc_html_e('Suchen', 'widerrufsbutton-wc'); ?></button>
            </p>
        </form>

        <table class="widefat striped wrb-list">
            <thead>
                <tr>
                    <th><?php esc_html_e('ID', 'widerrufsbutton-wc'); ?></th>
                    <th><?php esc_html_e('Eingang', 'widerrufsbutton-wc'); ?></th>
                    <th><?php esc_html_e('Name', 'widerrufsbutton-wc'); ?></th>
                    <th><?php esc_html_e('Bestellnr.', 'widerrufsbutton-wc'); ?></th>
                    <th><?php esc_html_e('Fall', 'widerrufsbutton-wc'); ?></th>
                    <th><?php esc_html_e('Status', 'widerrufsbutton-wc'); ?></th>
                    <th><?php esc_html_e('Mail', 'widerrufsbutton-wc'); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if ($rows === []) : ?>
                <tr><td colspan="8"><?php esc_html_e('Keine Eintraege.', 'widerrufsbutton-wc'); ?></td></tr>
            <?php else : ?>
                <?php foreach ($rows as $row) : ?>
                    <tr>
                        <td>#<?php echo (int) $row['id']; ?></td>
                        <td><?php echo esc_html((string) $row['received_at_local']); ?></td>
                        <td><?php echo esc_html((string) $row['customer_name']); ?></td>
                        <td><?php echo esc_html((string) $row['order_number']); ?></td>
                        <td><?php echo esc_html((string) $row['case_type']); ?></td>
                        <td><?php echo esc_html($this->status_label((string) $row['status'])); ?></td>
                        <td><?php echo $row['confirmation_mail_sent'] ? '&#10003;' : '&ndash;'; ?></td>
                        <td><a class="button button-small" href="<?php echo esc_url(add_query_arg('view', (int) $row['id'], $base)); ?>"><?php esc_html_e('Details', 'widerrufsbutton-wc'); ?></a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <?php
        $pages = (int) ceil($total / self::PER_PAGE);
        if ($pages > 1) {
            $links = paginate_links([
                'base'      => add_query_arg('paged', '%#%', $base),
                'format'    => '',
                'current'   => $paged,
                'total'     => $pages,
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
            ]);
            echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post((string) $links) . '</div></div>';
        }
    }

    /**
     * Rendert die Detailansicht inkl. Entwurfs-Vorschau + Freigabe-Buttons.
     */
    private function render_detail(int $id): void
    {
        $repo = new WithdrawalRepository();
        $row  = $repo->find($id);
        $base = menu_page_url(self::SLUG, false);

        if ($row === null) {
            echo '<p>' . esc_html__('Eintrag nicht gefunden.', 'widerrufsbutton-wc') . '</p>';
            return;
        }

        $context = [
            'reference'    => 'WRB-' . (int) $row['id'],
            'customer_name'=> (string) $row['customer_name'],
            'order_number' => (string) $row['order_number'],
            'reason'       => (string) $row['exclusion_reason'],
        ];

        $mailer    = new Mailer();
        $acceptance = $mailer->build_acceptance($context);
        $rejection  = $mailer->build_rejection_draft($context);

        echo '<p><a href="' . esc_url($base) . '">&laquo; ' . esc_html__('Zurueck zur Liste', 'widerrufsbutton-wc') . '</a></p>';

        // Stammdaten.
        $fields = [
            __('Eingang (lokal)', 'widerrufsbutton-wc')   => (string) $row['received_at_local'],
            __('Eingang (UTC)', 'widerrufsbutton-wc')     => (string) $row['received_at_utc'],
            __('Name', 'widerrufsbutton-wc')              => (string) $row['customer_name'],
            __('E-Mail', 'widerrufsbutton-wc')            => (string) $row['customer_email'],
            __('Bestellnummer', 'widerrufsbutton-wc')     => (string) $row['order_number'],
            __('Bestell-ID', 'widerrufsbutton-wc')        => (string) ($row['order_id'] ?? ''),
            __('Fall', 'widerrufsbutton-wc')              => (string) $row['case_type'] . ' ' . $this->case_hint((string) $row['case_type']),
            __('Fristbeginn (Snapshot)', 'widerrufsbutton-wc') => (string) $row['order_date_snapshot'],
            __('Frist (Tage)', 'widerrufsbutton-wc')      => (string) $row['deadline_days_snapshot'],
            __('Ausgeschlossen', 'widerrufsbutton-wc')    => $row['excluded_flag'] ? __('Ja', 'widerrufsbutton-wc') : __('Nein', 'widerrufsbutton-wc'),
            __('Ausschluss-Grund', 'widerrufsbutton-wc')  => (string) $row['exclusion_reason'],
            __('Eingangsbestaetigung', 'widerrufsbutton-wc') => $row['confirmation_mail_sent'] ? __('versendet', 'widerrufsbutton-wc') : __('nicht versendet', 'widerrufsbutton-wc'),
            __('Status', 'widerrufsbutton-wc')            => $this->status_label((string) $row['status']),
        ];

        echo '<table class="widefat wrb-detail"><tbody>';
        foreach ($fields as $label => $value) {
            echo '<tr><th scope="row">' . esc_html($label) . '</th><td>' . esc_html($value) . '</td></tr>';
        }
        echo '</tbody></table>';

        // Entwuerfe + Freigabe.
        echo '<div class="wrb-drafts">';
        $this->render_draft_box(
            __('Akzeptanz (Entwurf)', 'widerrufsbutton-wc'),
            $acceptance,
            (int) $row['id'],
            'acceptance',
            __('Akzeptanz freigeben & senden', 'widerrufsbutton-wc')
        );
        $this->render_draft_box(
            __('Ablehnung (Entwurf)', 'widerrufsbutton-wc'),
            $rejection,
            (int) $row['id'],
            'rejection',
            __('Ablehnung freigeben & senden', 'widerrufsbutton-wc')
        );
        echo '</div>';
    }

    /**
     * Box mit Entwurfsvorschau + Freigabe-Formular (Nonce).
     */
    private function render_draft_box(string $title, string $body, int $id, string $type, string $button): void
    {
        echo '<div class="wrb-draft">';
        echo '<h2>' . esc_html($title) . '</h2>';
        echo '<div class="wrb-draft__preview">' . wp_kses_post($body) . '</div>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="wrb_release_draft" />';
        echo '<input type="hidden" name="id" value="' . $id . '" />';
        echo '<input type="hidden" name="decision" value="' . esc_attr($type) . '" />';
        wp_nonce_field('wrb_release_draft_' . $id);
        echo '<button type="submit" class="button button-primary">' . esc_html($button) . '</button>';
        echo '</form>';
        echo '</div>';
    }

    /**
     * Verarbeitet die 1-Klick-Freigabe einer Entscheidung.
     */
    public function handle_release_draft(): void
    {
        if (!current_user_can(self::CAP)) {
            wp_die(esc_html__('Keine Berechtigung.', 'widerrufsbutton-wc'));
        }

        $id       = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $decision = isset($_POST['decision']) ? sanitize_key(wp_unslash($_POST['decision'])) : '';
        $base     = menu_page_url(self::SLUG, false);

        check_admin_referer('wrb_release_draft_' . $id);

        if ($id <= 0 || !in_array($decision, ['acceptance', 'rejection'], true)) {
            wp_safe_redirect(add_query_arg('wrb_msg', 'invalid', $base));
            exit;
        }

        $repo = new WithdrawalRepository();
        $row  = $repo->find($id);

        if ($row === null) {
            wp_safe_redirect(add_query_arg('wrb_msg', 'invalid', $base));
            exit;
        }

        $sent = (new Mailer())->send_decision((string) $row['customer_email'], $decision, [
            'reference'    => 'WRB-' . $id,
            'customer_name'=> (string) $row['customer_name'],
            'order_number' => (string) $row['order_number'],
            'reason'       => (string) $row['exclusion_reason'],
        ]);

        $detail = add_query_arg('view', $id, $base);

        if (!$sent) {
            wp_safe_redirect(add_query_arg('wrb_msg', 'failed', $detail));
            exit;
        }

        $repo->update_status($id, $decision === 'acceptance' ? 'erledigt' : 'abgelehnt');

        wp_safe_redirect(add_query_arg('wrb_msg', 'sent', $detail));
        exit;
    }

    /**
     * Lesbare Status-Bezeichnung.
     */
    private function status_label(string $status): string
    {
        $map = [
            'eingegangen'    => __('Eingegangen', 'widerrufsbutton-wc'),
            'in_bearbeitung' => __('In Bearbeitung', 'widerrufsbutton-wc'),
            'erledigt'       => __('Erledigt', 'widerrufsbutton-wc'),
            'abgelehnt'      => __('Abgelehnt', 'widerrufsbutton-wc'),
        ];

        return $map[$status] ?? $status;
    }

    /**
     * Kurzer Hinweis zum Fall.
     */
    private function case_hint(string $case): string
    {
        $map = [
            CaseResolver::CASE_A => __('(in Frist, nicht ausgeschlossen)', 'widerrufsbutton-wc'),
            CaseResolver::CASE_B => __('(in Frist, ausgeschlossen)', 'widerrufsbutton-wc'),
            CaseResolver::CASE_C => __('(ausserhalb Frist)', 'widerrufsbutton-wc'),
        ];

        return $map[$case] ?? '';
    }
}
