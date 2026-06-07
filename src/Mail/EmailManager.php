<?php
/**
 * Registrierung und Ansteuerung der Widerruf-WC_Emails.
 *
 * @package Entruencer\Widerruf
 */

declare(strict_types=1);

namespace Entruencer\Widerruf\Mail;

use Entruencer\Widerruf\Mail\Emails\Acceptance;
use Entruencer\Widerruf\Mail\Emails\Acknowledgement;
use Entruencer\Widerruf\Mail\Emails\NewWithdrawalAdmin;
use Entruencer\Widerruf\Mail\Emails\Rejection;
use Entruencer\Widerruf\Mail\Emails\WithdrawalEmail;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bindet die vier Widerruf-Mails ins native WooCommerce-Mailsystem ein und
 * bietet schlanke Helfer fuer Versand (trigger) und Backend-Vorschau (preview).
 */
final class EmailManager
{
    /**
     * Letzter wp_mail-Fehler (fuer Admin-Sichtbarkeit).
     */
    private static string $last_error = '';

    /**
     * Mapping interner Typ -> Klassen-Key im WC-Mailer.
     */
    private const MAP = [
        'acknowledgement' => 'WRB_Acknowledgement',
        'acceptance'      => 'WRB_Acceptance',
        'rejection'       => 'WRB_Rejection',
        'new_admin'       => 'WRB_NewWithdrawalAdmin',
    ];

    /**
     * Haengt die Registrierung + Fehler-Capture ein.
     */
    public function register(): void
    {
        add_filter('woocommerce_email_classes', [self::class, 'register_classes']);
        add_action('wp_mail_failed', [self::class, 'capture_error']);
    }

    /**
     * Registriert die WC_Email-Klassen im WooCommerce-Mailer.
     *
     * @param array<string, \WC_Email> $emails
     *
     * @return array<string, \WC_Email>
     */
    public static function register_classes(array $emails): array
    {
        $emails['WRB_Acknowledgement']     = new Acknowledgement();
        $emails['WRB_Acceptance']          = new Acceptance();
        $emails['WRB_Rejection']           = new Rejection();
        $emails['WRB_NewWithdrawalAdmin']  = new NewWithdrawalAdmin();

        return $emails;
    }

    /**
     * Speichert die letzte wp_mail-Fehlermeldung.
     *
     * @param \WP_Error $error
     */
    public static function capture_error($error): void
    {
        if ($error instanceof \WP_Error) {
            self::$last_error = $error->get_error_message();
        }
    }

    /**
     * Liefert die letzte Fehlermeldung (leer = kein Fehler).
     */
    public static function last_error(): string
    {
        return self::$last_error;
    }

    /**
     * Versendet die Mail des angegebenen Typs.
     *
     * @param string               $type       acknowledgement|acceptance|rejection|new_admin.
     * @param array<string, mixed> $withdrawal Withdrawal-Datensatz (Repository-Array).
     *
     * @return bool Versanderfolg.
     */
    public static function trigger(string $type, array $withdrawal): bool
    {
        $email = self::instance($type);

        return $email !== null ? $email->trigger($withdrawal) : false;
    }

    /**
     * Liefert das gerenderte Mail-HTML fuer die Backend-Vorschau (kein Versand).
     *
     * @param array<string, mixed> $withdrawal
     */
    public static function preview(string $type, array $withdrawal): string
    {
        $email = self::instance($type);

        if ($email === null) {
            return '';
        }

        $email->prepare_preview($withdrawal);

        // style_inline() wie beim echten Versand -> Vorschau entspricht der Mail.
        return $email->style_inline($email->get_content());
    }

    /**
     * Holt die WC_Email-Instanz aus dem WooCommerce-Mailer.
     */
    private static function instance(string $type): ?WithdrawalEmail
    {
        if (!isset(self::MAP[$type]) || !function_exists('WC')) {
            return null;
        }

        $mailer = WC()->mailer();
        if (!$mailer instanceof \WC_Emails) {
            return null;
        }

        $emails = $mailer->get_emails();
        $email  = $emails[self::MAP[$type]] ?? null;

        return $email instanceof WithdrawalEmail ? $email : null;
    }
}
