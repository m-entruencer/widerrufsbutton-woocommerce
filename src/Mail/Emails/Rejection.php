<?php
/**
 * WC_Email: Ablehnung des Widerrufs an den Kunden (nach 1-Klick-Freigabe).
 *
 * @package Entruencer\Widerruf
 */

declare(strict_types=1);

namespace Entruencer\Widerruf\Mail\Emails;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Mitteilung der Ablehnung. Versand nur nach manueller Freigabe im Admin.
 */
final class Rejection extends WithdrawalEmail
{
    public function __construct()
    {
        $this->id             = 'wrb_rejection';
        $this->customer_email = true;
        $this->title          = __('Widerruf: Ablehnung', 'widerrufsbutton-wc');
        $this->description    = __('Mitteilung an den Kunden, dass dem Widerruf nicht entsprochen wird. Versand nur nach manueller Freigabe im Backend.', 'widerrufsbutton-wc');
        $this->template_html  = 'emails/customer-rejection.php';
        $this->template_plain = 'emails/plain/customer-rejection.php';
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
        return __('Information zu deinem Widerruf', 'widerrufsbutton-wc');
    }

    public function get_default_heading(): string
    {
        return __('Information zu deinem Widerruf', 'widerrufsbutton-wc');
    }
}
