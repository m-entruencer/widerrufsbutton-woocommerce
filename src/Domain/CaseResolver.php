<?php
/**
 * Bestimmt den Bearbeitungsfall A/B/C.
 *
 * @package Entruencer\Widerruf
 */

declare(strict_types=1);

namespace Entruencer\Widerruf\Domain;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Leitet aus Frist-Status und Ausschluss-Flag den Bearbeitungsfall ab.
 *
 *  A = in Frist + nicht ausgeschlossen  -> Auto-Akzeptanz.
 *  B = in Frist + ausgeschlossen        -> Eingangsbestaetigung + Ablehnungs-ENTWURF.
 *  C = ausserhalb Frist                 -> Entwurf.
 *
 * Die neutrale Eingangsbestaetigung geht in ALLEN Faellen sofort raus.
 * Eine automatische Ablehnung ist NICHT Default (Rechtsklaerung offen).
 */
final class CaseResolver
{
    public const CASE_A = 'A';
    public const CASE_B = 'B';
    public const CASE_C = 'C';

    /**
     * Bestimmt den Fall.
     *
     * @param bool $inDeadline  Liegt der Widerruf in der Frist?
     * @param bool $excluded    Ist (mind. eine Position) vom Widerruf ausgeschlossen?
     *
     * @return string Einer der CASE_*-Werte.
     */
    public function resolve(bool $inDeadline, bool $excluded): string
    {
        if (!$inDeadline) {
            return self::CASE_C;
        }

        return $excluded ? self::CASE_B : self::CASE_A;
    }
}
