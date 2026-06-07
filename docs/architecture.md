# Technische Architektur

## Überblick

Eigenständiges WooCommerce-Plugin. Klare Schichtentrennung:
Admin / Frontend / Mail / Domain / Repository / Product / Install.

## PSR-4 / Namespace

- Composer-Autoloading, Namespace `Entruencer\Widerruf\` -> Ordner `src/`.
- Bootstrap in `widerrufsbutton-wc.php` (Header, Defines, HPOS-Deklaration,
  Activation/Deactivation, plugins_loaded -> `Plugin::instance()->register()`).
- Autoloader: bevorzugt `vendor/autoload.php`; fehlt es, registriert die Bootstrap-Datei
  einen schlanken PSR-4-Fallback (`spl_autoload_register`, `Entruencer\Widerruf\` -> `src/`).
  Damit läuft das Plugin out-of-the-box ohne `composer install` (relevant für ZIP-Deploy).

## Ordnerstruktur

```
widerrufsbutton-wc/
  widerrufsbutton-wc.php     Bootstrap + Plugin-Header
  uninstall.php             Löschen nur bei aktivem Setting (Default: nein)
  composer.json             PSR-4-Mapping
  src/
    Plugin.php              Wiring aller Subsysteme (Singleton)
    Install/Migrator.php    dbDelta, Schema-Version, Activation/Deactivation
    Admin/Menu.php          WC-Submenue "Widerrufe", Liste/Detail, 1-Klick-Freigabe
    Admin/Settings.php      Settings-Schema (Frist, Seite, Frontend-Design)
    Admin/SetupNotice.php   Admin-Hinweise (Setup-Status, SMTP, Migration)
    Frontend/Form.php       Shortcode [widerrufsbutton] + Submit-Verarbeitung
    Domain/DeadlineCalculator.php  Fristberechnung
    Domain/CaseResolver.php Fall A/B/C
    Domain/OrderMatcher.php Match ohne Enumeration
    Repository/WithdrawalRepository.php  CRUD Custom Table
    Mail/EmailManager.php   Registrierung + Trigger/Preview der WC_Emails
    Mail/Emails/*.php       WC_Email-Klassen (Basis WithdrawalEmail + 4 Mail-Typen)
    Install/PageInstaller.php  Auto-Anlage der Widerruf-Seite (idempotent)
    Product/ExclusionField.php  Produkt-Datentab-Feld
  templates/
    emails/{customer-acknowledgement,customer-acceptance,customer-rejection,admin-new-withdrawal}.php
    emails/plain/...        Plain-Text-Varianten der Mail-Templates
    frontend/form.php
  assets/css/{public,admin}.css
  assets/js/public.js
  languages/
  docs/
```

## HPOS (High-Performance Order Storage)

- `before_woocommerce_init`: `FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true)`.
- Bestellzugriff ausschließlich via `wc_get_order()` / `$order->get_meta()`.
- NIEMALS direkte Queries auf wp_posts/postmeta für Bestelldaten.

## Custom-Table-Schema ({prefix}entruencer_withdrawals)

| Spalte | Typ | Hinweis |
|---|---|---|
| id | BIGINT UNSIGNED AI | PK |
| order_id | BIGINT UNSIGNED NULL | FK zur WC-Order |
| order_number | VARCHAR(64) NULL | |
| customer_email | VARCHAR(191) NULL | KEY |
| customer_name | VARCHAR(191) NULL | |
| received_at_utc | DATETIME NULL | |
| received_at_local | DATETIME NULL | |
| case_type | CHAR(1) NULL | A/B/C |
| deadline_days_snapshot | SMALLINT UNSIGNED NULL | |
| order_date_snapshot | DATETIME NULL | Fristbeginn |
| excluded_flag | TINYINT(1) DEFAULT 0 | |
| exclusion_reason | TEXT NULL | |
| waiver_proven | TINYINT(1) DEFAULT 0 | |
| confirmation_mail_sent | TINYINT(1) DEFAULT 0 | |
| status | VARCHAR(20) DEFAULT 'eingegangen' | KEY |
| created_at | DATETIME NOT NULL | |

- Anlage via `dbDelta()` in `Migrator::create_tables()`.
- Schema-Version in wp_option `wrb_schema_version` (`Migrator::SCHEMA_VERSION`).
- Upgrade-Check bei `admin_init` (`Migrator::maybe_upgrade`).

## Mail-Layer (native WooCommerce-Mails, WC_Email)

- Vier WC_Email-Klassen unter `src/Mail/Emails/` mit gemeinsamer Basis `WithdrawalEmail`:
  `Acknowledgement`, `Acceptance`, `Rejection` (Kunde) und `NewWithdrawalAdmin` (Betreiber).
- `EmailManager` registriert sie via Filter `woocommerce_email_classes` und bietet
  `trigger($type, $withdrawal)` (Versand) sowie `preview($type, $withdrawal)`
  (Backend-Vorschau via `WC_Email::get_content()`, kein Versand).
- Absender, Betreff, Überschrift, Layout/Branding und An-/Abschaltung laufen über
  WooCommerce -> Einstellungen -> E-Mails. Versand via `WC_Email::send()` (intern `wp_mail()`).
- Templates überschreibbar nach WC-Konvention `<theme>/woocommerce/emails/...` (HTML + Plain),
  Fallback im Plugin via `template_base = templates/`.
- Eingangsbestätigung wird automatisch beim Submit ausgelöst (`confirmation_mail_sent`
  persistiert); Akzeptanz/Ablehnung nur nach 1-Klick-Freigabe; Betreiber-Benachrichtigung
  automatisch bei jedem Eingang.
- Versandfehler -> `wp_mail_failed`-Capture (EmailManager::last_error) + Admin-Sichtbarkeit.

## Settings-Schema

`wrb_settings` (Array). Felder: deadline_days (Default 14), deadline_start_basis
(created/paid/completed), withdrawal_page_slug (Default 'widerruf'), withdrawal_page_id,
confirmation_message (Frontend-Text), accent_color, background_color, text_color, radius,
delete_data_on_uninstall (Default false). Mail-Einstellungen liegen NICHT mehr hier,
sondern in den WooCommerce-Mail-Settings (WC_Email). Setup-Version-Flag: `wrb_setup_version`.
Vollständige Referenz: docs/anpassung.md.

## Update-Sicherheit

- Daten in eigener Custom Table (kein CPT) -> stabil über Theme-/Plugin-Updates.
- Schema-Versionsflag erlaubt kontrollierte Migrationen.
- Deaktivierung löscht KEINE Daten; Deinstallation nur bei explizitem Setting.

## White-Label-Mechanik

- Keine festen Markenfarben/Texte im Code.
- Sichtbare Werte aus Settings; CSS über Custom Properties `--wrb-accent`,
  `--wrb-bg`, `--wrb-text`, `--wrb-radius`.
- Eine konkrete Shop-Brand (z.B. cream/sage) ist nur ein Konfig-Beispiel, NICHT Default.
