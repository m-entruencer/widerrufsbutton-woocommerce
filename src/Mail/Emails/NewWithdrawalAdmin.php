<?php
/**
 * WC_Email: Benachrichtigung an den Shop-Betreiber bei neuem Widerruf.
 *
 * @package Entruencer\Widerruf
 */

declare(strict_types=1);

namespace Entruencer\Widerruf\Mail\Emails;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interne Benachrichtigung an den Betreiber, sobald ein Widerruf eingeht -
 * damit kein Vorgang unbemerkt liegen bleibt. Empfaenger ueber die
 * WC-Mail-Einstellungen konfigurierbar (Default: Admin-E-Mail).
 */
final class NewWithdrawalAdmin extends WithdrawalEmail
{
    public function __construct()
    {
        $this->id             = 'wrb_new_withdrawal_admin';
        $this->customer_email = false;
        $this->title          = __('Widerruf: Benachrichtigung an den Betreiber', 'widerrufsbutton-wc');
        $this->description    = __('Interne Mail an den Shop-Betreiber bei jedem neuen Widerruf, mit Direktlink zur Freigabe.', 'widerrufsbutton-wc');
        $this->template_html  = 'emails/admin-new-withdrawal.php';
        $this->template_plain = 'emails/plain/admin-new-withdrawal.php';
        $this->placeholders   = [
            '{reference}'     => '',
            '{customer_name}' => '',
            '{order_number}'  => '',
        ];

        $this->init_template_base();

        parent::__construct();

        $this->recipient = $this->get_option('recipient', get_option('admin_email'));
    }

    public function get_default_subject(): string
    {
        return __('Neuer Widerruf eingegangen ({reference})', 'widerrufsbutton-wc');
    }

    public function get_default_heading(): string
    {
        return __('Neuer Widerruf', 'widerrufsbutton-wc');
    }

    /**
     * Empfaenger ist der Betreiber (aus den WC-Mail-Settings), nicht der Kunde.
     *
     * @param array<string, mixed> $withdrawal
     */
    protected function resolve_recipient(array $withdrawal): string
    {
        $recipient = (string) $this->get_option('recipient', get_option('admin_email'));

        return $recipient !== '' ? $recipient : (string) get_option('admin_email');
    }

    /**
     * Ergaenzt das Standard-Settings-Schema um ein Empfaenger-Feld.
     */
    public function init_form_fields(): void
    {
        parent::init_form_fields();

        $this->form_fields = array_merge(
            [
                'recipient' => [
                    'title'       => __('Empfänger', 'widerrufsbutton-wc'),
                    'type'        => 'text',
                    'description' => sprintf(
                        /* translators: %s = Standard-Admin-E-Mail. */
                        __('Empfänger der Benachrichtigung. Mehrere durch Komma trennen. Standard: %s', 'widerrufsbutton-wc'),
                        esc_html((string) get_option('admin_email'))
                    ),
                    'placeholder' => (string) get_option('admin_email'),
                    'default'     => (string) get_option('admin_email'),
                    'desc_tip'    => true,
                ],
            ],
            $this->form_fields
        );
    }
}
