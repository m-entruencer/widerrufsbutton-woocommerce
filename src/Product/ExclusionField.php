<?php
/**
 * Produkt-Datentab-Feld "Vom Widerruf ausgeschlossen".
 *
 * @package Entruencer\Widerruf
 */

declare(strict_types=1);

namespace Entruencer\Widerruf\Product;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fuegt im WooCommerce-Produkt-Datentab ein Flag "Vom Widerruf ausgeschlossen"
 * plus Freitext-Grund hinzu und speichert beides als Produkt-Meta.
 *
 * Das Plugin leitet daraus pro Bestellposition die Ausschluss-Information ab.
 */
final class ExclusionField
{
    /**
     * Meta-Key des Ausschluss-Flags.
     */
    public const META_EXCLUDED = '_wrb_withdrawal_excluded';

    /**
     * Meta-Key des Ausschluss-Grunds.
     */
    public const META_REASON = '_wrb_withdrawal_exclusion_reason';

    /**
     * Haengt die Produkt-Hooks ein.
     */
    public function register(): void
    {
        add_action('woocommerce_product_options_general_product_data', [$this, 'render_fields']);
        add_action('woocommerce_admin_process_product_object', [$this, 'save_fields']);
    }

    /**
     * Rendert Checkbox + Grund-Feld im Produkt-Datentab.
     */
    public function render_fields(): void
    {
        echo '<div class="options_group">';

        woocommerce_wp_checkbox([
            'id'          => self::META_EXCLUDED,
            'label'       => __('Vom Widerruf ausgeschlossen', 'widerrufsbutton-wc'),
            'description' => __('Position gilt als vom Widerruf ausgeschlossen (z.B. digitaler Sofort-Download mit Verzicht).', 'widerrufsbutton-wc'),
        ]);

        woocommerce_wp_textarea_input([
            'id'          => self::META_REASON,
            'label'       => __('Ausschluss-Grund', 'widerrufsbutton-wc'),
            'description' => __('Wird intern als Begruendung uebernommen.', 'widerrufsbutton-wc'),
            'desc_tip'    => true,
        ]);

        echo '</div>';
    }

    /**
     * Speichert die Felder als Produkt-Meta (HPOS-konform via Produkt-Objekt).
     *
     * Nonce/Capability werden vom WooCommerce-Produkt-Save-Flow getragen.
     *
     * @param \WC_Product $product Das zu speichernde Produktobjekt.
     */
    public function save_fields(\WC_Product $product): void
    {
        $excluded = isset($_POST[self::META_EXCLUDED]) ? 'yes' : 'no';
        $product->update_meta_data(self::META_EXCLUDED, $excluded);

        $reason = isset($_POST[self::META_REASON])
            ? sanitize_textarea_field(wp_unslash($_POST[self::META_REASON]))
            : '';
        $product->update_meta_data(self::META_REASON, $reason);
    }
}
