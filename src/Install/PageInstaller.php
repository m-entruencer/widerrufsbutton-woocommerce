<?php
/**
 * Legt die oeffentliche Widerruf-Seite mit dem Shortcode an (DAU-Setup).
 *
 * @package Entruencer\Widerruf
 */

declare(strict_types=1);

namespace Entruencer\Widerruf\Install;

use Entruencer\Widerruf\Admin\Settings;
use Entruencer\Widerruf\Frontend\Form;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Stellt sicher, dass genau eine Seite mit dem Shortcode [widerrufsbutton]
 * existiert. Idempotent: legt nichts doppelt an, respektiert vorhandene Seiten
 * und einen frei gewaehlten Slug.
 */
final class PageInstaller
{
    /**
     * Sorgt fuer eine vorhandene Widerruf-Seite und gibt deren ID zurueck (0 = fehlgeschlagen).
     */
    public static function ensure_page(): int
    {
        $settings  = get_option(Settings::OPTION, []);
        $settings  = is_array($settings) ? $settings : [];
        $shortcode = Form::SHORTCODE;

        // 1. Bereits zugeordnete, gueltige Seite?
        $pageId = (int) ($settings['withdrawal_page_id'] ?? 0);
        if ($pageId > 0) {
            $post = get_post($pageId);
            if (
                $post instanceof \WP_Post
                && $post->post_status !== 'trash'
                && has_shortcode((string) $post->post_content, $shortcode)
            ) {
                return $pageId;
            }
        }

        // 2. Vorhandene Seite mit dem Shortcode (keine Dublette anlegen)?
        $existing = self::find_page_with_shortcode($shortcode);
        if ($existing > 0) {
            self::store_page_id($settings, $existing);
            return $existing;
        }

        // 3. Neu anlegen. WP vergibt bei Slug-Kollision automatisch einen eindeutigen Slug.
        $slug = sanitize_title((string) ($settings['withdrawal_page_slug'] ?? 'widerruf'));
        if ($slug === '') {
            $slug = 'widerruf';
        }

        $newId = wp_insert_post([
            'post_title'   => __('Widerruf', 'widerrufsbutton-wc'),
            'post_name'    => $slug,
            'post_content' => '[' . $shortcode . ']',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);

        if (!is_wp_error($newId) && (int) $newId > 0) {
            self::store_page_id($settings, (int) $newId);
            return (int) $newId;
        }

        return 0;
    }

    /**
     * Sucht die erste Seite, die den Shortcode enthaelt.
     */
    private static function find_page_with_shortcode(string $shortcode): int
    {
        $ids = get_posts([
            'post_type'        => 'page',
            'post_status'      => ['publish', 'draft', 'pending', 'private'],
            'numberposts'      => 200,
            'fields'           => 'ids',
            'suppress_filters' => true,
        ]);

        foreach ($ids as $id) {
            $content = (string) get_post_field('post_content', (int) $id);
            if (has_shortcode($content, $shortcode)) {
                return (int) $id;
            }
        }

        return 0;
    }

    /**
     * Speichert die ermittelte Seiten-ID in den Plugin-Settings.
     *
     * @param array<string, mixed> $settings
     */
    private static function store_page_id(array $settings, int $id): void
    {
        $settings['withdrawal_page_id'] = $id;
        update_option(Settings::OPTION, $settings);
    }
}
