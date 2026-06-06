<?php
/**
 * Bestell-Match ohne Enumeration.
 *
 * @package Entruencer\Widerruf
 */

declare(strict_types=1);

namespace Entruencer\Widerruf\Domain;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Findet eine Bestellung anhand der Kombination Bestellnummer + E-Mail.
 *
 * Sicherheitsprinzip (Enumeration-Schutz):
 *  - Nur exakte Kombi aus Bestellnummer UND E-Mail matcht.
 *  - Rate-Limiting pro IP (und ggf. pro Bestellnummer).
 *  - Die aufrufende Schicht gibt IMMER eine neutrale Antwort aus,
 *    unabhaengig davon, ob ein Match existiert.
 *
 * HPOS: Zugriff ausschliesslich via wc_get_order() / WC-Order-Queries,
 * niemals direkt auf wp_posts/postmeta.
 */
final class OrderMatcher
{
    /**
     * Versucht, eine Bestellung zu matchen.
     *
     * @param string $orderNumber Vom Besucher eingegebene Bestellnummer.
     * @param string $email       Vom Besucher eingegebene E-Mail.
     *
     * @return \WC_Order|null Die Bestellung bei exaktem Match, sonst null.
     */
    public function match(string $orderNumber, string $email): ?\WC_Order
    {
        $orderNumber = trim($orderNumber);
        $email       = trim($email);

        if ($orderNumber === '' || $email === '') {
            return null;
        }

        /**
         * Loest eine Bestellnummer in eine WC_Order auf.
         *
         * Default: die Bestellnummer entspricht der Order-ID (WC-Standard).
         * Shops mit Bestellnummer-Plugins (z.B. Sequential Order Numbers)
         * koennen die Aufloesung hierueber ueberschreiben.
         *
         * @param \WC_Order|null $order       Vorbelegung (null).
         * @param string         $orderNumber Eingegebene Bestellnummer.
         */
        $order = apply_filters('wrb_resolve_order_by_number', null, $orderNumber);

        if (!$order instanceof \WC_Order) {
            $numeric = (int) preg_replace('/[^0-9]/', '', $orderNumber);

            if ($numeric <= 0) {
                return null;
            }

            $order = wc_get_order($numeric);
        }

        if (!$order instanceof \WC_Order) {
            return null;
        }

        // Billing-E-Mail case-insensitive vergleichen. Kein Treffer -> null,
        // der Aufrufer antwortet immer neutral (kein Enumeration-Hinweis).
        $billing = (string) $order->get_billing_email();

        if ($billing === '' || strcasecmp($billing, $email) !== 0) {
            return null;
        }

        return $order;
    }
}
