<?php
/**
 * WC_Email: Eingangsbestaetigung an den Kunden (automatisch, gesetzliche Pflicht).
 *
 * @package Entruencer\Widerruf
 */

declare(strict_types=1);

namespace Entruencer\Widerruf\Mail\Emails;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Neutrale Eingangsbestaetigung mit Datum und Uhrzeit. Geht in allen Faellen
 * (A/B/C) automatisch raus.
 */
final class Acknowledgement extends WithdrawalEmail
{
    public function __construct()
    {
        $this->id             = 'wrb_acknowledgement';
        $this->customer_email = true;
        $this->title          = __('Widerruf: Eingangsbestätigung', 'widerrufsbutton-wc');
        $this->description    = __('Neutrale, gesetzlich verpflichtende Eingangsbestätigung an den Kunden. Geht in allen Fällen automatisch raus.', 'widerrufsbutton-wc');
        $this->template_html  = 'emails/customer-acknowledgement.php';
        $this->template_plain = 'emails/plain/customer-acknowledgement.php';
        $this->placeholders   = [
            '{reference}'     => '',
            '{customer_name}' => '',
            '{order_number}'  => '',
        ];

        $this->init_template_base();

        parent::__construct();
    }

    public function get_default_subject(): string
    {
        return __('Eingangsbestätigung deines Widerrufs', 'widerrufsbutton-wc');
    }

    public function get_default_heading(): string
    {
        return __('Eingang deines Widerrufs bestätigt', 'widerrufsbutton-wc');
    }
}
