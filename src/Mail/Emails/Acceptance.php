<?php
/**
 * WC_Email: Akzeptanz des Widerrufs an den Kunden (nach 1-Klick-Freigabe).
 *
 * @package Entruencer\Widerruf
 */

declare(strict_types=1);

namespace Entruencer\Widerruf\Mail\Emails;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bestaetigung der Akzeptanz. Versand nur nach manueller Freigabe im Admin.
 */
final class Acceptance extends WithdrawalEmail
{
    public function __construct()
    {
        $this->id             = 'wrb_acceptance';
        $this->customer_email = true;
        $this->title          = __('Widerruf: Akzeptanz', 'widerrufsbutton-wc');
        $this->description    = __('Bestaetigung an den Kunden, dass der Widerruf akzeptiert wurde. Versand nur nach manueller Freigabe im Backend.', 'widerrufsbutton-wc');
        $this->template_html  = 'emails/customer-acceptance.php';
        $this->template_plain = 'emails/plain/customer-acceptance.php';
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
        return __('Dein Widerruf wurde akzeptiert', 'widerrufsbutton-wc');
    }

    public function get_default_heading(): string
    {
        return __('Widerruf akzeptiert', 'widerrufsbutton-wc');
    }
}
