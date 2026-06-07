<?php
/**
 * Oeffentliche zweistufige Widerruf-Seite (Shortcode + Verarbeitung).
 *
 * @package Entruencer\Widerruf
 */

declare(strict_types=1);

namespace Entruencer\Widerruf\Frontend;

use Entruencer\Widerruf\Admin\Settings;
use Entruencer\Widerruf\Domain\CaseResolver;
use Entruencer\Widerruf\Domain\DeadlineCalculator;
use Entruencer\Widerruf\Domain\OrderMatcher;
use Entruencer\Widerruf\Mail\EmailManager;
use Entruencer\Widerruf\Product\ExclusionField;
use Entruencer\Widerruf\Repository\WithdrawalRepository;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Stellt den oeffentlichen Widerruf-Flow bereit (Shortcode [widerrufsbutton]).
 *
 * Stufe 1: Button "Vertrag widerrufen".
 * Stufe 2: Formular (Name, Bestellnummer, E-Mail) + "Widerruf bestaetigen".
 *
 * Sicherheit: Nonce + Honeypot + Rate-Limiting. Antwort immer neutral,
 * kein Hinweis ob die Bestellung existiert.
 */
final class Form
{
    public const SHORTCODE = 'widerrufsbutton';

    /**
     * Haengt Shortcode und Submit-Verarbeitung ein.
     */
    public function register(): void
    {
        add_shortcode(self::SHORTCODE, [$this, 'render_shortcode']);

        add_action('admin_post_nopriv_wrb_submit', [$this, 'handle_submit']);
        add_action('admin_post_wrb_submit', [$this, 'handle_submit']);

        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
    }

    /**
     * Registriert (nicht enqueued) die Frontend-Assets.
     */
    public function register_assets(): void
    {
        wp_register_style('wrb-public', WRB_PLUGIN_URL . 'assets/css/public.css', [], WRB_VERSION);
        wp_register_script('wrb-public', WRB_PLUGIN_URL . 'assets/js/public.js', [], WRB_VERSION, true);
    }

    /**
     * Rendert die Widerruf-Seite ueber das ueberschreibbare Template.
     *
     * @param array<string, mixed>|string $atts Shortcode-Attribute.
     */
    public function render_shortcode($atts = []): string
    {
        wp_enqueue_style('wrb-public');
        wp_enqueue_script('wrb-public');

        // Neutrale Bestaetigung nach Absenden.
        if (isset($_GET['wrb']) && $_GET['wrb'] === 'ok') {
            return $this->render_confirmation();
        }

        $style    = $this->inline_style();
        $redirect = $this->current_url();

        $located = locate_template('widerrufsbutton-wc/frontend/form.php');
        if ($located === '') {
            $located = WRB_PLUGIN_DIR . 'templates/frontend/form.php';
        }

        if (!is_readable($located)) {
            return '';
        }

        ob_start();
        if ($style !== '') {
            echo '<style>' . $style . '</style>'; // phpcs:ignore -- nur Custom-Properties, escaped erzeugt.
        }
        include $located;
        return (string) ob_get_clean();
    }

    /**
     * Baut die neutrale Bestaetigungsanzeige.
     */
    private function render_confirmation(): string
    {
        $msg = (string) Settings::get('confirmation_message');

        if (trim($msg) === '') {
            $msg = __('Vielen Dank. Der Eingang deiner Erklaerung wurde bestaetigt. Du erhaeltst in Kuerze eine Bestaetigung per E-Mail.', 'widerrufsbutton-wc');
        }

        $style = $this->inline_style();

        return ($style !== '' ? '<style>' . $style . '</style>' : '')
            . '<div class="wrb-widget"><div class="wrb-confirmation">'
            . wp_kses_post(wpautop($msg))
            . '</div></div>';
    }

    /**
     * Baut den scoped Inline-Style mit den White-Label-Custom-Properties.
     */
    private function inline_style(): string
    {
        $vars = Settings::custom_properties();
        if ($vars === []) {
            return '';
        }

        $decls = '';
        foreach ($vars as $prop => $value) {
            // Property-Name fix, Wert ist bereits per Settings::sanitize geprueft.
            $decls .= sprintf('%s:%s;', $prop, esc_attr($value));
        }

        return '.wrb-widget{' . $decls . '}';
    }

    /**
     * Aktuelle URL (fuer Redirect zurueck nach Submit).
     */
    private function current_url(): string
    {
        $path = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        return esc_url_raw(home_url($path));
    }

    /**
     * Verarbeitet das abgesendete Widerruf-Formular.
     *
     * Antwort ist IMMER neutral und identisch, egal ob eine Bestellung existiert.
     */
    public function handle_submit(): void
    {
        $redirect = isset($_POST['wrb_redirect']) ? esc_url_raw(wp_unslash($_POST['wrb_redirect'])) : home_url('/');
        $redirect = add_query_arg('wrb', 'ok', $redirect);

        // 1. Nonce.
        if (!isset($_POST['wrb_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wrb_nonce'])), 'wrb_submit')) {
            wp_safe_redirect($redirect);
            exit;
        }

        // 2. Honeypot: gefuellt -> stille neutrale Antwort.
        if (!empty($_POST['wrb_website'])) {
            wp_safe_redirect($redirect);
            exit;
        }

        // 3. Rate-Limit.
        if (!$this->check_rate_limit()) {
            wp_safe_redirect($redirect);
            exit;
        }

        // 4. Sanitize.
        $name        = sanitize_text_field(wp_unslash($_POST['wrb_name'] ?? ''));
        $orderNumber = sanitize_text_field(wp_unslash($_POST['wrb_order_number'] ?? ''));
        $email       = sanitize_email(wp_unslash($_POST['wrb_email'] ?? ''));

        if ($name === '' || $orderNumber === '' || !is_email($email)) {
            wp_safe_redirect($redirect);
            exit;
        }

        // 5. Bestell-Match (ohne Enumeration).
        $order = (new OrderMatcher())->match($orderNumber, $email);

        // 6./7. Snapshots + Fall bestimmen, Datensatz anlegen.
        $now       = new \DateTimeImmutable('now', wp_timezone());
        $orderDate = $this->resolve_order_date($order, $now);
        $deadline  = (int) Settings::get('deadline_days');

        $inDeadline = (new DeadlineCalculator())->isWithinDeadline($orderDate, $deadline, $now);

        [$excluded, $reason] = $this->resolve_exclusion($order);

        $case = (new CaseResolver())->resolve($inDeadline, $excluded);

        $repo = new WithdrawalRepository();
        $id   = $repo->insert([
            'order_id'               => $order ? $order->get_id() : null,
            'order_number'           => $orderNumber,
            'customer_email'         => $email,
            'customer_name'          => $name,
            'received_at_utc'        => gmdate('Y-m-d H:i:s'),
            'received_at_local'      => $now->format('Y-m-d H:i:s'),
            'case_type'              => $case,
            'deadline_days_snapshot' => $deadline,
            'order_date_snapshot'    => $orderDate->format('Y-m-d H:i:s'),
            'excluded_flag'          => $excluded ? 1 : 0,
            'exclusion_reason'       => $reason,
            'waiver_proven'          => 0,
            'confirmation_mail_sent' => 0,
            'status'                 => 'eingegangen',
        ]);

        // 8. Mails: Eingangsbestaetigung (Kunde, immer/neutral) + Benachrichtigung (Betreiber).
        if ($id !== null) {
            $withdrawal = $repo->find($id) ?? [];

            if ($withdrawal !== []) {
                // Neutrale Eingangsbestaetigung (gesetzliche Pflicht).
                if (EmailManager::trigger('acknowledgement', $withdrawal)) {
                    $repo->update($id, ['confirmation_mail_sent' => 1]);
                }

                // Betreiber benachrichtigen, damit kein Vorgang liegen bleibt.
                EmailManager::trigger('new_admin', $withdrawal);
            }
        }

        // 9. Immer neutrale Bestaetigungsseite.
        wp_safe_redirect($redirect);
        exit;
    }

    /**
     * Ermittelt den Fristbeginn anhand der Setting deadline_start_basis.
     */
    private function resolve_order_date(?\WC_Order $order, \DateTimeImmutable $fallback): \DateTimeImmutable
    {
        if (!$order instanceof \WC_Order) {
            return $fallback;
        }

        $basis = (string) Settings::get('deadline_start_basis');

        $date = match ($basis) {
            'paid'      => $order->get_date_paid(),
            'completed' => $order->get_date_completed(),
            default     => $order->get_date_created(),
        };

        if ($date === null) {
            $date = $order->get_date_created();
        }

        if ($date === null) {
            return $fallback;
        }

        return (new \DateTimeImmutable())->setTimestamp($date->getTimestamp());
    }

    /**
     * Prueft, ob mind. eine Bestellposition vom Widerruf ausgeschlossen ist.
     *
     * @return array{0: bool, 1: string} [ausgeschlossen, Gruende].
     */
    private function resolve_exclusion(?\WC_Order $order): array
    {
        if (!$order instanceof \WC_Order) {
            return [false, ''];
        }

        $excluded = false;
        $reasons  = [];

        foreach ($order->get_items() as $item) {
            if (!$item instanceof \WC_Order_Item_Product) {
                continue;
            }

            $product = $item->get_product();
            if (!$product instanceof \WC_Product) {
                continue;
            }

            if ($product->get_meta(ExclusionField::META_EXCLUDED) === 'yes') {
                $excluded = true;
                $r        = (string) $product->get_meta(ExclusionField::META_REASON);
                if ($r !== '') {
                    $reasons[] = $r;
                }
            }
        }

        return [$excluded, implode(' | ', array_unique($reasons))];
    }

    /**
     * Einfaches IP-basiertes Rate-Limit via Transient.
     */
    private function check_rate_limit(): bool
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

        /**
         * Filtert die Rate-Limit-Konfiguration.
         *
         * @param array{max:int, window:int} $config max Anfragen pro window-Sekunden.
         */
        $config = apply_filters('wrb_rate_limit', ['max' => 5, 'window' => 600]);
        $max    = max(1, (int) ($config['max'] ?? 5));
        $window = max(60, (int) ($config['window'] ?? 600));

        if ($ip === '') {
            return true;
        }

        $key   = 'wrb_rl_' . md5($ip);
        $count = (int) get_transient($key);

        if ($count >= $max) {
            return false;
        }

        set_transient($key, $count + 1, $window);

        return true;
    }
}
