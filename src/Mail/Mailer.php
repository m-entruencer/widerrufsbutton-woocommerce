<?php
/**
 * Mail-Versand fuer den Widerruf-Flow.
 *
 * @package Entruencer\Widerruf
 */

declare(strict_types=1);

namespace Entruencer\Widerruf\Mail;

use Entruencer\Widerruf\Admin\Settings;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Baut und versendet die Widerruf-Mails ueber wp_mail().
 *
 *  - Eingangsbestaetigung (send_acknowledgement): gesetzliche Pflicht,
 *    neutral, mit Datum UND Uhrzeit. Geht automatisch raus.
 *  - Akzeptanz / Ablehnung: NUR nach manueller 1-Klick-Freigabe im Admin
 *    (send_decision). build_*() liefern die Vorschau-Bodies.
 *
 * Templates sind ueberschreibbare PHP-Templates (Theme-Override moeglich).
 */
final class Mailer
{
    /**
     * Letzter wp_mail-Fehler (fuer Admin-Sichtbarkeit).
     */
    private static string $last_error = '';

    /**
     * Haengt Mail-bezogene Hooks ein.
     */
    public function register(): void
    {
        add_action('wp_mail_failed', [self::class, 'capture_error']);
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
     * Versendet die neutrale Eingangsbestaetigung.
     *
     * @param string               $to       Empfaenger-E-Mail.
     * @param \DateTimeImmutable    $received Eingangszeitpunkt (Datum + Uhrzeit Pflicht).
     * @param array<string, mixed>  $context  Zusatzdaten (reference etc.).
     *
     * @return bool Versanderfolg.
     */
    public function send_acknowledgement(string $to, \DateTimeImmutable $received, array $context = []): bool
    {
        $context = $this->base_context($context);
        $ts      = $received->getTimestamp();

        $context['datum']   = wp_date(get_option('date_format'), $ts);
        $context['uhrzeit'] = wp_date(get_option('time_format'), $ts);

        $subject = $this->subject('acknowledgement', __('Eingangsbestaetigung deines Widerrufs', 'widerrufsbutton-wc'));
        $body    = $this->body('acknowledgement', 'emails/acknowledgement.php', $context);

        return $this->send($to, $subject, $body, $context);
    }

    /**
     * Baut die Akzeptanz-Mail (Fall A) - Vorschau/Body.
     *
     * @param array<string, mixed> $context
     */
    public function build_acceptance(array $context = []): string
    {
        return $this->body('acceptance', 'emails/acceptance.php', $this->base_context($context));
    }

    /**
     * Baut den Ablehnungs-Entwurf (Fall B/C) - Vorschau/Body.
     *
     * @param array<string, mixed> $context Inkl. Ablehnungsgrund ({reason}).
     */
    public function build_rejection_draft(array $context = []): string
    {
        return $this->body('rejection', 'emails/rejection.php', $this->base_context($context));
    }

    /**
     * Versendet eine freigegebene Entscheidung (Akzeptanz oder Ablehnung).
     *
     * @param string               $to      Empfaenger.
     * @param string               $type    'acceptance' | 'rejection'.
     * @param array<string, mixed> $context
     *
     * @return bool Versanderfolg.
     */
    public function send_decision(string $to, string $type, array $context = []): bool
    {
        $context = $this->base_context($context);

        if ($type === 'acceptance') {
            $subject = $this->subject('acceptance', __('Dein Widerruf wurde akzeptiert', 'widerrufsbutton-wc'));
            $body    = $this->build_acceptance($context);
        } else {
            $subject = $this->subject('rejection', __('Information zu deinem Widerruf', 'widerrufsbutton-wc'));
            $body    = $this->build_rejection_draft($context);
        }

        return $this->send($to, $subject, $body, $context);
    }

    /**
     * Liefert die letzte Fehlermeldung (leer = kein Fehler).
     */
    public static function last_error(): string
    {
        return self::$last_error;
    }

    /**
     * Ergaenzt den Kontext um Standardwerte (brand_name etc.).
     *
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function base_context(array $context): array
    {
        $brand = (string) Settings::get('brand_name');
        if ($brand === '') {
            $brand = (string) get_bloginfo('name');
        }

        return array_merge([
            'brand_name'   => $brand,
            'datum'        => '',
            'uhrzeit'      => '',
            'reference'    => '',
            'reason'       => '',
            'customer_name'=> '',
            'order_number' => '',
        ], $context);
    }

    /**
     * Liefert die Betreffzeile aus den Settings oder einen Default.
     */
    private function subject(string $case, string $default): string
    {
        $custom = (string) Settings::get('subject_' . $case);
        return $custom !== '' ? $custom : $default;
    }

    /**
     * Liefert den Mail-Body: konfigurierter Text aus den Settings (mit
     * Platzhalter-Ersetzung) oder das ueberschreibbare PHP-Template.
     *
     * @param array<string, mixed> $context
     */
    private function body(string $case, string $template, array $context): string
    {
        $custom = (string) Settings::get('body_' . $case);

        if (trim($custom) !== '') {
            return wpautop($this->replace_placeholders($custom, $context));
        }

        return $this->load_template($template, $context);
    }

    /**
     * Ersetzt {platzhalter} im Text durch Kontextwerte.
     *
     * @param array<string, mixed> $context
     */
    private function replace_placeholders(string $text, array $context): string
    {
        foreach ($context as $key => $value) {
            if (is_scalar($value)) {
                $text = str_replace('{' . $key . '}', (string) $value, $text);
            }
        }

        return $text;
    }

    /**
     * Versendet eine Mail via wp_mail (HTML), persistiert Fehlerstatus.
     *
     * @param array<string, mixed> $context
     */
    private function send(string $to, string $subject, string $body, array $context): bool
    {
        if (!is_email($to)) {
            self::$last_error = 'invalid recipient';
            return false;
        }

        self::$last_error = '';

        add_filter('wp_mail_content_type', [self::class, 'html_content_type']);
        $ok = wp_mail($to, $subject, $body, $this->headers());
        remove_filter('wp_mail_content_type', [self::class, 'html_content_type']);

        if (!$ok && self::$last_error === '') {
            self::$last_error = 'wp_mail returned false';
        }

        return $ok;
    }

    /**
     * Content-Type-Filter (HTML-Mails).
     */
    public static function html_content_type(): string
    {
        return 'text/html';
    }

    /**
     * Baut die Mail-Header (From + Reply-To) aus den Settings.
     *
     * @return array<int, string>
     */
    private function headers(): array
    {
        $name  = (string) Settings::get('sender_name');
        $email = (string) Settings::get('sender_email');
        $reply = (string) Settings::get('reply_to');

        $headers = [];

        if (is_email($email)) {
            $from      = $name !== '' ? sprintf('%s <%s>', $name, $email) : $email;
            $headers[] = 'From: ' . $from;
        }

        if (is_email($reply)) {
            $headers[] = 'Reply-To: ' . $reply;
        }

        /**
         * Filtert die Mail-Header (z.B. fuer zusaetzliche Cc/Bcc).
         *
         * @param array<int, string> $headers
         */
        return apply_filters('wrb_email_headers', $headers);
    }

    /**
     * Laedt ein Mail-Template mit Theme-Override-Moeglichkeit.
     *
     * Suchreihenfolge: <theme>/widerrufsbutton-wc/<relative> -> Plugin/templates/<relative>.
     *
     * @param string               $relative Pfad relativ zu templates/.
     * @param array<string, mixed> $vars     An das Template uebergebene Variablen.
     */
    private function load_template(string $relative, array $vars = []): string
    {
        $located = locate_template('widerrufsbutton-wc/' . $relative);

        if ($located === '') {
            $located = WRB_PLUGIN_DIR . 'templates/' . $relative;
        }

        if (!is_readable($located)) {
            return '';
        }

        // Platzhalter-Werte als Variablen verfuegbar machen.
        extract($vars, EXTR_SKIP);

        ob_start();
        include $located;
        return (string) ob_get_clean();
    }
}
