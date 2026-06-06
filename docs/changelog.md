# Changelog

## 0.1.0
- Erste funktionale Implementierung (Skeleton-Stubs ausgefuellt):
  - Autoloader-Fallback in der Bootstrap-Datei (laeuft ohne `composer install`).
  - DeadlineCalculator (Fristlogik) und OrderMatcher (Match ohne Enumeration, Filter
    `wrb_resolve_order_by_number`).
  - WithdrawalRepository: insert/find/list/count/update mit Prepared Statements und
    Spalten-/Status-Whitelist.
  - Settings: White-Label-Schema inkl. `deadline_start_basis`, Farben/Radius,
    confirmation_message; Sanitisierung; eingebettetes Settings-Formular.
  - Frontend/Form: Shortcode-Rendering mit scoped White-Label-Inline-Style, Submit-
    Verarbeitung (Nonce, Honeypot, Rate-Limit via `wrb_rate_limit`, Snapshots),
    immer neutrale Antwort.
  - Mailer: automatische Eingangsbestaetigung, Entwurfs-Bodies und freigegebener
    Entscheidungsversand, Theme-ueberschreibbare Templates, Header-Filter
    `wrb_email_headers`, Fehler-Capturing.
  - Admin/Menu: paginierte Liste mit Suche/Filter, Detailansicht mit Entwurfs-
    Vorschau und 1-Klick-Freigabe (Akzeptanz/Ablehnung), Settings-Tab.
  - Product/ExclusionField: Produkt-Flag + Grund als Produkt-Meta.
  - Mail-Templates auf echte Variablen umgestellt; uninstall.php loescht nur bei
    aktivem Setting.
- Entscheidung: keine automatische Entscheidung - auch Fall A wird Entwurf, nur die
  Eingangsbestaetigung geht automatisch raus. Einbindung nur via Shortcode.
- Neue Doku `docs/anpassung.md` (zentrale Anpassungsreferenz).

## 0.1.0-dev
- Projekt-Scaffold angelegt (Doku + Plugin-Skeleton, Stubs mit TODO, noch keine
  fachliche Implementierung).
