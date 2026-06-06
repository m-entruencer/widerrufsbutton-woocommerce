<?php
/**
 * Fristberechnung fuer den Widerruf.
 *
 * @package Entruencer\Widerruf
 */

declare(strict_types=1);

namespace Entruencer\Widerruf\Domain;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Berechnet, ob ein Widerruf innerhalb der Widerrufsfrist liegt.
 *
 * Fristbeginn-Annahmen (rechtlich vor Implementierung zu bestaetigen,
 * siehe docs/rechtsfragen.md):
 *  - Waren: Fristbeginn mit Erhalt der Ware durch den Verbraucher.
 *  - Digitale Inhalte / Dienstleistungen: Fristbeginn grundsaetzlich mit
 *    Vertragsschluss. Bei sofortiger Ausfuehrung kann die Frist mit
 *    wirksamem Verzicht entfallen (offen, Rechtsklaerung erforderlich).
 *
 * Dieser Calculator arbeitet bewusst mit einem uebergebenen Fristbeginn
 * (order_date_snapshot) und der konfigurierten Fristlaenge. Die Ableitung
 * des korrekten Fristbeginns je Produkttyp erfolgt vorgelagert.
 */
final class DeadlineCalculator
{
    /**
     * Ermittelt, ob der Widerruf in der Frist liegt.
     *
     * @param \DateTimeImmutable $orderDate     Fristbeginn (z.B. Bestell-/Erhalt-/Vertragsdatum).
     * @param int                $deadlineDays  Konfigurierte Fristlaenge in Tagen.
     * @param \DateTimeImmutable $withdrawalDate Zeitpunkt der Widerrufserklaerung.
     *
     * @return bool true, wenn innerhalb der Frist.
     */
    public function isWithinDeadline(
        \DateTimeImmutable $orderDate,
        int $deadlineDays,
        \DateTimeImmutable $withdrawalDate
    ): bool {
        if ($deadlineDays < 0) {
            $deadlineDays = 0;
        }

        // Fristbeginn ist der Tag des Fristbeginn-Ereignisses; die Frist endet
        // mit Ablauf des letzten Tages (Tagesende). Beide Werte werden auf die
        // jeweilige Tagesgrenze normiert, damit Uhrzeiten die Frist nicht verkuerzen.
        $deadlineEnd = $orderDate
            ->setTime(0, 0, 0)
            ->modify('+' . $deadlineDays . ' days')
            ->setTime(23, 59, 59);

        return $withdrawalDate <= $deadlineEnd;
    }
}
