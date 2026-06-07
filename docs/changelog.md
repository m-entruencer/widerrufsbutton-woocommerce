# Changelog

## 0.2.1
- Umlaute in allen nutzer-sichtbaren Strings korrigiert (Admin-Liste, Detailansicht,
  Einstellungen, SetupNotice, Frontend-Bestaetigung, Mail-Einstellungen in WC).

## 0.2.0
- Mailsystem von eigenem wp_mail() auf native WooCommerce-Mails (WC_Email) umgestellt.
  Vier Mail-Typen: Eingangsbestaetigung, Akzeptanz, Ablehnung (Kunde) und neue
  Benachrichtigung an den Betreiber. Absender, Betreff, Texte und Layout laufen ueber
  WooCommerce -> Einstellungen -> E-Mails.
  - Neue Klassen: src/Mail/EmailManager.php, src/Mail/Emails/{WithdrawalEmail,
    Acknowledgement,Acceptance,Rejection,NewWithdrawalAdmin}.php.
  - Neue Templates templates/emails/{customer-*,admin-new-withdrawal}.php + plain/-Varianten.
  - Entfernt: src/Mail/Mailer.php und die alten templates/emails/{acknowledgement,
    acceptance,rejection}.php.
- Admin-Benachrichtigung bei neuem Widerruf (Empfaenger via WC-Settings, Direktlink zur Freigabe).
- DAU-Setup: src/Install/PageInstaller.php legt bei Aktivierung/Update idempotent eine
  Widerruf-Seite mit Shortcode an (Slug via Setting withdrawal_page_slug, keine Dublette).
  Eingebunden in Migrator::activate() und Migrator::maybe_upgrade() (Setup-Version-Flag).
- Admin-Hinweise: src/Admin/SetupNotice.php (Setup-Status, SMTP-Empfehlung, Migrationshinweis).
- Settings verschlankt: sender_*/subject_*/body_*/brand_* entfernt; neu withdrawal_page_id/-slug.
- Detail-Vorschau der Entwuerfe jetzt im echten WC-Mail-Layout (iframe, kein Versand).
- Theme-Override-Konvention der Mail-Templates: <theme>/woocommerce/emails/...

## 0.1.0 - Public Release (2026-06-06)
- Plugin als Open-Source-Community-Projekt veroeffentlicht:
  github.com/m-entruencer/widerrufsbutton-woocommerce (PUBLIC, GPL-2.0-or-later).
  Kuratierte Kopie ohne interne Docs, eigene History, README mit KI-Schnellstart +
  Haftungsausschluss, GPLv2-LICENSE, Release v0.1.0 mit installierbarem ZIP.
- ZIP-Build-Script build-zip.ps1: Forward-Slash-Pfade statt Compress-Archive
  (Backslash-Bug, auf Linux/WP-CLI unbrauchbar).
- Live-Einsatz auf echtem Shop (Niccis Seite) erfolgreich verlaufen.

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
