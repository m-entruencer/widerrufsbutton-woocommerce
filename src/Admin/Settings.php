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
 * Frist, Widerruf-Seite, oeffentlicher Bestaetigungstext und Frontend-Design.
 * Mail-Einstellungen (Absender/Betreff/Texte/Layout) laufen ueber die
 * WooCommerce-Mail-Settings (WC_Email), nicht mehr hier.
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

            // Widerruf-Seite (Auto-Setup).
            'withdrawal_page_id'        => 0,
            'withdrawal_page_slug'      => 'widerruf',

            // Oeffentliche neutrale Bestaetigung nach Absenden (Frontend, keine Mail).
            'confirmation_message'      => '',

            // Frontend-Design (CSS Custom Properties).
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

        $out['withdrawal_page_id'] = max(0, (int) ($input['withdrawal_page_id'] ?? 0));

        $slug = sanitize_title((string) ($input['withdrawal_page_slug'] ?? 'widerruf'));
        $out['withdrawal_page_slug'] = $slug !== '' ? $slug : 'widerruf';

        $out['confirmation_message'] = wp_kses_post((string) ($input['confirmation_message'] ?? ''));

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

            <h2><?php esc_html_e('Widerruf-Seite', 'widerrufsbutton-wc'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="wrb-page-slug"><?php esc_html_e('Seiten-Slug', 'widerrufsbutton-wc'); ?></label></th>
                    <td>
                        <input type="text" id="wrb-page-slug" name="<?php echo esc_attr(self::OPTION); ?>[withdrawal_page_slug]" value="<?php echo esc_attr((string) $s['withdrawal_page_slug']); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e('Slug der automatisch angelegten Widerruf-Seite (Standard: widerruf). Wird nur beim Anlegen genutzt, falls noch keine Seite mit dem Shortcode existiert.', 'widerrufsbutton-wc'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="wrb-page-id"><?php esc_html_e('Zugeordnete Seite', 'widerrufsbutton-wc'); ?></label></th>
                    <td>
                        <?php
                        wp_dropdown_pages([
                            'name'              => self::OPTION . '[withdrawal_page_id]',
                            'id'                => 'wrb-page-id',
                            'selected'          => (int) $s['withdrawal_page_id'],
                            'show_option_none'  => __('- nicht zugeordnet -', 'widerrufsbutton-wc'),
                            'option_none_value' => '0',
                        ]);
                        ?>
                        <p class="description"><?php esc_html_e('Seite mit dem Shortcode [widerrufsbutton]. Wird beim Auto-Setup automatisch gesetzt.', 'widerrufsbutton-wc'); ?></p>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e('E-Mails', 'widerrufsbutton-wc'); ?></h2>
            <p class="description">
                <?php
                printf(
                    /* translators: %s = Link zu den WooCommerce-E-Mail-Einstellungen. */
                    esc_html__('Absender, Betreff, Texte und Layout der Widerruf-Mails werden zentral verwaltet unter %s.', 'widerrufsbutton-wc'),
                    '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=email')) . '">' . esc_html__('WooCommerce -> Einstellungen -> E-Mails', 'widerrufsbutton-wc') . '</a>'
                );
                ?>
            </p>

            <h2><?php esc_html_e('Öffentliche Bestätigung', 'widerrufsbutton-wc'); ?></h2>
            <table class="form-table" role="presentation">
                <?php $this->textarea_row($s, 'confirmation_message', __('Bestätigungstext nach Absenden', 'widerrufsbutton-wc')); ?>
            </table>

            <h2><?php esc_html_e('Design (Frontend-Widget)', 'widerrufsbutton-wc'); ?></h2>
            <table class="form-table" role="presentation">
                <?php
                $this->text_row($s, 'accent_color', __('Akzentfarbe (Hex, z.B. #1DA3C9)', 'widerrufsbutton-wc'));
                $this->text_row($s, 'background_color', __('Hintergrundfarbe (Hex)', 'widerrufsbutton-wc'));
                $this->text_row($s, 'text_color', __('Textfarbe (Hex)', 'widerrufsbutton-wc'));
                $this->text_row($s, 'radius', __('Eckenradius (z.B. 12px)', 'widerrufsbutton-wc'));
                ?>
            </table>

            <h2><?php esc_html_e('Deinstallation', 'widerrufsbutton-wc'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Daten bei Deinstallation löschen', 'widerrufsbutton-wc'); ?></th>
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
