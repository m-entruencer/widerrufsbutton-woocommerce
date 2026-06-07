<?php
/**
 * Abstrakte Basis fuer alle Widerruf-Mails (auf WC_Email aufsetzend).
 *
 * @package Entruencer\Widerruf
 */

declare(strict_types=1);

namespace Entruencer\Widerruf\Mail\Emails;

use Entruencer\Widerruf\Domain\CaseResolver;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gemeinsame Logik der vier Widerruf-WC_Emails:
 *  - Kontext-Aufbau aus dem Withdrawal-Datensatz (Array aus dem Repository).
 *  - Template-Rendering (HTML + Plain) mit Theme-Override unter
 *    <theme>/woocommerce/emails/...
 *  - trigger() (Versand) und prepare_preview() (Vorschau ohne Versand).
 *
 * Konkrete Klassen setzen nur id/title/description/template/Default-Texte
 * und (fuer die Admin-Mail) den Empfaenger.
 */
abstract class WithdrawalEmail extends \WC_Email
{
    /**
     * Aktueller Withdrawal-Datensatz (Repository-Array).
     *
     * @var array<string, mixed>
     */
    protected array $withdrawal = [];

    /**
     * Setzt das Plugin-Template-Verzeichnis als Fallback-Basis.
     * Muss aus dem Konstruktor der Subklasse VOR parent::__construct() laufen.
     */
    protected function init_template_base(): void
    {
        $this->template_base = WRB_PLUGIN_DIR . 'templates/';
    }

    /**
     * Uebernimmt den Withdrawal-Datensatz in object + Platzhalter.
     *
     * @param array<string, mixed> $withdrawal
     */
    public function set_withdrawal(array $withdrawal): void
    {
        $this->withdrawal = $withdrawal;
        $this->object     = $withdrawal;

        $args = $this->template_args();

        $this->placeholders = array_merge($this->placeholders, [
            '{reference}'     => $args['reference'],
            '{customer_name}' => $args['customer_name'],
            '{order_number}'  => $args['order_number'],
        ]);
    }

    /**
     * Baut die an die Templates uebergebenen Variablen (einheitlich fuer alle
     * Templates, damit keine undefinierten Variablen auftreten).
     *
     * @return array<string, mixed>
     */
    protected function template_args(): array
    {
        $w  = $this->withdrawal;
        $id = (int) ($w['id'] ?? 0);

        $ts = isset($w['received_at_utc']) && $w['received_at_utc'] !== ''
            ? strtotime((string) $w['received_at_utc'] . ' UTC')
            : 0;

        $datum   = $ts ? wp_date((string) get_option('date_format'), $ts) : '';
        $uhrzeit = $ts ? wp_date((string) get_option('time_format'), $ts) : '';

        $case = (string) ($w['case_type'] ?? '');

        return [
            'reference'     => 'WRB-' . $id,
            'datum'         => (string) $datum,
            'uhrzeit'       => (string) $uhrzeit,
            'customer_name' => (string) ($w['customer_name'] ?? ''),
            'order_number'  => (string) ($w['order_number'] ?? ''),
            'reason'        => (string) ($w['exclusion_reason'] ?? ''),
            'case_type'     => $case,
            'case_hint'     => $this->case_hint($case),
            'status_label'  => $this->status_label((string) ($w['status'] ?? '')),
            'detail_url'    => $id > 0 ? admin_url('admin.php?page=wrb-withdrawals&view=' . $id) : '',
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => !$this->customer_email,
            'plain_text'    => false,
            'email'         => $this,
        ];
    }

    /**
     * Rendert den HTML-Inhalt (ohne Versand).
     */
    public function get_content_html(): string
    {
        return wc_get_template_html($this->template_html, $this->template_args(), '', $this->template_base);
    }

    /**
     * Rendert den Plain-Text-Inhalt (ohne Versand).
     */
    public function get_content_plain(): string
    {
        $args = $this->template_args();
        $args['plain_text'] = true;

        return wc_get_template_html($this->template_plain, $args, '', $this->template_base);
    }

    /**
     * Loest den Versand aus.
     *
     * @param array<string, mixed> $withdrawal
     *
     * @return bool Versanderfolg.
     */
    public function trigger(array $withdrawal): bool
    {
        $this->setup_locale();
        $this->set_withdrawal($withdrawal);
        $this->recipient = $this->resolve_recipient($withdrawal);

        $sent = false;
        if ($this->is_enabled() && $this->get_recipient() !== '') {
            $sent = $this->send(
                $this->get_recipient(),
                $this->get_subject(),
                $this->get_content(),
                $this->get_headers(),
                $this->get_attachments()
            );
        }

        $this->restore_locale();

        return (bool) $sent;
    }

    /**
     * Bereitet eine Instanz fuer die Vorschau vor (kein Versand).
     *
     * @param array<string, mixed> $withdrawal
     */
    public function prepare_preview(array $withdrawal): void
    {
        $this->set_withdrawal($withdrawal);
    }

    /**
     * Ermittelt den Empfaenger. Default: Kunde. Admin-Mail ueberschreibt das.
     *
     * @param array<string, mixed> $withdrawal
     */
    protected function resolve_recipient(array $withdrawal): string
    {
        return (string) ($withdrawal['customer_email'] ?? '');
    }

    /**
     * Lesbare Status-Bezeichnung.
     */
    protected function status_label(string $status): string
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
     * Kurzer Hinweis zum Fall A/B/C.
     */
    protected function case_hint(string $case): string
    {
        $map = [
            CaseResolver::CASE_A => __('(in Frist, nicht ausgeschlossen)', 'widerrufsbutton-wc'),
            CaseResolver::CASE_B => __('(in Frist, ausgeschlossen)', 'widerrufsbutton-wc'),
            CaseResolver::CASE_C => __('(ausserhalb Frist)', 'widerrufsbutton-wc'),
        ];

        return $map[$case] ?? '';
    }
}
