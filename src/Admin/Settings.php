<?php
/**
 * Plugin-Einstellungen (White-Label-Konfiguration).
 *
 * @package Entruencer\Widerruf
 */

declare(strict_types=1);

namespace Entruencer\Widerruf\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Definiert und verwaltet das Settings-Schema.
 *
 * Alle Marken-, Mail- und Textwerte sind hier konfigurierbar.
 * KEINE festen Markenfarben/Texte im Code (White-Label-Prinzip).
 */
final class Settings
{
    /**
     * Option-Key, unter dem alle Settings als Array gespeichert werden.
     */
    public const OPTION = 'wrb_settings';

    /**
     * Settings-API-Gruppe (Nonce/Capability via options.php).
     */
    public const GROUP = 'wrb_settings_group';

    /**
     * Erlaubte Werte fuer den Fristbeginn.
     */
    public const DEADLINE_BASES = ['created', 'paid', 'completed'];

    /**
     * Haengt die Settings-Registrierung ein.
     */
    public function register(): void
    {
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Liefert das Default-Settings-Schema (neutral / white-label).
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            // Fristberechnung.
            'deadline_days'             => 14,
            'deadline_start_basis'      => 'created',

            // Absender / Mail-Kopf.
            'sender_name'               => '',
            'sender_email'              => '',
            'reply_to'                  => '',

            // Betreffzeilen pro Fall.
            'subject_acknowledgement'   => '',
            'subject_acceptance'        => '',
            'subject_rejection'         => '',

            // Mail-Texte pro Fall (Platzhalter-faehig: {brand_name}, {datum}, {uhrzeit}, {reference}, {reason}).
            'body_acknowledgement'      => '',
            'body_acceptance'           => '',
            'body_rejection'            => '',

            // Oeffentliche neutrale Bestaetigung nach Absenden.
            'confirmation_message'      => '',

            // White-Label-Felder.
            'brand_name'                => '',
            'brand_logo_url'            => '',
            'accent_color'              => '',
            'background_color'          => '',
            'text_color'                => '',
            'radius'                    => '',

            // Deinstallation.
            'delete_data_on_uninstall'  => false,
        ];
    }

    /**
     * Liest die effektiven Settings (gespeichert + Defaults).
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        $saved = get_option(self::OPTION, []);
        return array_merge(self::defaults(), is_array($saved) ? $saved : []);
    }

    /**
     * Liefert einen einzelnen Setting-Wert.
     *
     * @return mixed
     */
    public static function get(string $key)
    {
        $all = self::all();
        return $all[$key] ?? null;
    }

    /**
     * CSS-Custom-Properties aus den White-Label-Settings.
     * Leere Werte werden ausgelassen (CSS-Default greift).
     *
     * @return array<string, string>
     */
    public static function custom_properties(): array
    {
        $all  = self::all();
        $map  = [
            '--wrb-accent' => 'accent_color',
            '--wrb-bg'     => 'background_color',
            '--wrb-text'   => 'text_color',
            '--wrb-radius' => 'radius',
        ];
        $vars = [];

        foreach ($map as $prop => $key) {
            $value = trim((string) ($all[$key] ?? ''));
            if ($value !== '') {
                $vars[$prop] = $value;
            }
        }

        return $vars;
    }

    /**
     * Registriert die Settings ueber die Settings-API.
     */
    public function register_settings(): void
    {
        register_setting(
            self::GROUP,
            self::OPTION,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitize'],
                'default'           => self::defaults(),
            ]
        );
    }

    /**
     * Sanitisiert das gesamte Settings-Array.
     *
     * @param mixed $input
     *
     * @return array<string, mixed>
     */
    public function sanitize($input): array
    {
        $input = is_array($input) ? $input : [];
        $out   = self::defaults();

        $out['deadline_days'] = max(0, (int) ($input['deadline_days'] ?? 14));

        $basis = (string) ($input['deadline_start_basis'] ?? 'created');
        $out['deadline_start_basis'] = in_array($basis, self::DEADLINE_BASES, true) ? $basis : 'created';

        $out['sender_name'] = sanitize_text_field((string) ($input['sender_name'] ?? ''));
        $out['sender_email'] = sanitize_email((string) ($input['sender_email'] ?? ''));
        $out['reply_to'] = sanitize_email((string) ($input['reply_to'] ?? ''));

        foreach (['subject_acknowledgement', 'subject_acceptance', 'subject_rejection'] as $key) {
            $out[$key] = sanitize_text_field((string) ($input[$key] ?? ''));
        }

        foreach (['body_acknowledgement', 'body_acceptance', 'body_rejection', 'confirmation_message'] as $key) {
            $out[$key] = wp_kses_post((string) ($input[$key] ?? ''));
        }

        $out['brand_name'] = sanitize_text_field((string) ($input['brand_name'] ?? ''));
        $out['brand_logo_url'] = esc_url_raw((string) ($input['brand_logo_url'] ?? ''));

        foreach (['accent_color', 'background_color', 'text_color'] as $key) {
            $color = sanitize_hex_color((string) ($input[$key] ?? ''));
            $out[$key] = $color ?? '';
        }

        // Radius: Zahl + optionale CSS-Einheit (px/rem/em/%), sonst leer.
        $radius = trim((string) ($input['radius'] ?? ''));
        $out['radius'] = preg_match('/^\d+(\.\d+)?(px|rem|em|%)?$/', $radius) === 1 ? $radius : '';

        $out['delete_data_on_uninstall'] = !empty($input['delete_data_on_uninstall']);

        return $out;
    }

    /**
     * Rendert das Settings-Formular (eingebettet im Menu-Tab).
     */
    public function render(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $s = self::all();
        ?>
        <form method="post" action="options.php" class="wrb-settings-form">
            <?php settings_fields(self::GROUP); ?>

            <h2><?php esc_html_e('Frist', 'widerrufsbutton-wc'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="wrb-deadline-days"><?php esc_html_e('Widerrufsfrist (Tage)', 'widerrufsbutton-wc'); ?></label></th>
                    <td><input type="number" min="0" id="wrb-deadline-days" name="<?php echo esc_attr(self::OPTION); ?>[deadline_days]" value="<?php echo esc_attr((string) $s['deadline_days']); ?>" class="small-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="wrb-deadline-basis"><?php esc_html_e('Fristbeginn', 'widerrufsbutton-wc'); ?></label></th>
                    <td>
                        <select id="wrb-deadline-basis" name="<?php echo esc_attr(self::OPTION); ?>[deadline_start_basis]">
                            <option value="created" <?php selected($s['deadline_start_basis'], 'created'); ?>><?php esc_html_e('Bestelldatum (Vertragsschluss)', 'widerrufsbutton-wc'); ?></option>
                            <option value="paid" <?php selected($s['deadline_start_basis'], 'paid'); ?>><?php esc_html_e('Zahlungsdatum', 'widerrufsbutton-wc'); ?></option>
                            <option value="completed" <?php selected($s['deadline_start_basis'], 'completed'); ?>><?php esc_html_e('Abschlussdatum (Lieferung/Erfuellung)', 'widerrufsbutton-wc'); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e('Rechtlich abhaengig vom Produkttyp - siehe docs/rechtsfragen.md.', 'widerrufsbutton-wc'); ?></p>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e('Absender', 'widerrufsbutton-wc'); ?></h2>
            <table class="form-table" role="presentation">
                <?php
                $this->text_row($s, 'sender_name', __('Absender-Name', 'widerrufsbutton-wc'));
                $this->text_row($s, 'sender_email', __('Absender-E-Mail', 'widerrufsbutton-wc'), 'email');
                $this->text_row($s, 'reply_to', __('Antwort-an (Reply-To)', 'widerrufsbutton-wc'), 'email');
                ?>
            </table>

            <h2><?php esc_html_e('Mail-Texte', 'widerrufsbutton-wc'); ?></h2>
            <p class="description"><?php esc_html_e('Platzhalter: {brand_name}, {datum}, {uhrzeit}, {reference}, {reason}. Leer = neutraler Standardtext.', 'widerrufsbutton-wc'); ?></p>
            <table class="form-table" role="presentation">
                <?php
                $this->text_row($s, 'subject_acknowledgement', __('Betreff Eingangsbestaetigung', 'widerrufsbutton-wc'));
                $this->textarea_row($s, 'body_acknowledgement', __('Text Eingangsbestaetigung', 'widerrufsbutton-wc'));
                $this->text_row($s, 'subject_acceptance', __('Betreff Akzeptanz', 'widerrufsbutton-wc'));
                $this->textarea_row($s, 'body_acceptance', __('Text Akzeptanz (Entwurf)', 'widerrufsbutton-wc'));
                $this->text_row($s, 'subject_rejection', __('Betreff Ablehnung', 'widerrufsbutton-wc'));
                $this->textarea_row($s, 'body_rejection', __('Text Ablehnung (Entwurf)', 'widerrufsbutton-wc'));
                ?>
            </table>

            <h2><?php esc_html_e('Oeffentliche Bestaetigung', 'widerrufsbutton-wc'); ?></h2>
            <table class="form-table" role="presentation">
                <?php $this->textarea_row($s, 'confirmation_message', __('Bestaetigungstext nach Absenden', 'widerrufsbutton-wc')); ?>
            </table>

            <h2><?php esc_html_e('White-Label / Design', 'widerrufsbutton-wc'); ?></h2>
            <table class="form-table" role="presentation">
                <?php
                $this->text_row($s, 'brand_name', __('Markenname', 'widerrufsbutton-wc'));
                $this->text_row($s, 'brand_logo_url', __('Logo-URL', 'widerrufsbutton-wc'), 'url');
                $this->text_row($s, 'accent_color', __('Akzentfarbe (Hex, z.B. #1DA3C9)', 'widerrufsbutton-wc'));
                $this->text_row($s, 'background_color', __('Hintergrundfarbe (Hex)', 'widerrufsbutton-wc'));
                $this->text_row($s, 'text_color', __('Textfarbe (Hex)', 'widerrufsbutton-wc'));
                $this->text_row($s, 'radius', __('Eckenradius (z.B. 12px)', 'widerrufsbutton-wc'));
                ?>
            </table>

            <h2><?php esc_html_e('Deinstallation', 'widerrufsbutton-wc'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Daten bei Deinstallation loeschen', 'widerrufsbutton-wc'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[delete_data_on_uninstall]" value="1" <?php checked(!empty($s['delete_data_on_uninstall'])); ?> />
                            <?php esc_html_e('Tabelle und Einstellungen bei Deinstallation entfernen (Default: aus, rechtliche Aufbewahrung).', 'widerrufsbutton-wc'); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
        <?php
    }

    /**
     * Rendert eine einfache Text/Email/URL-Zeile.
     *
     * @param array<string, mixed> $s
     */
    private function text_row(array $s, string $key, string $label, string $type = 'text'): void
    {
        ?>
        <tr>
            <th scope="row"><label for="wrb-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
            <td><input type="<?php echo esc_attr($type); ?>" id="wrb-<?php echo esc_attr($key); ?>" name="<?php echo esc_attr(self::OPTION); ?>[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr((string) ($s[$key] ?? '')); ?>" class="regular-text" /></td>
        </tr>
        <?php
    }

    /**
     * Rendert eine Textarea-Zeile.
     *
     * @param array<string, mixed> $s
     */
    private function textarea_row(array $s, string $key, string $label): void
    {
        ?>
        <tr>
            <th scope="row"><label for="wrb-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
            <td><textarea id="wrb-<?php echo esc_attr($key); ?>" name="<?php echo esc_attr(self::OPTION); ?>[<?php echo esc_attr($key); ?>]" rows="4" class="large-text"><?php echo esc_textarea((string) ($s[$key] ?? '')); ?></textarea></td>
        </tr>
        <?php
    }
}
