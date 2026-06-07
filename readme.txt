== Widerrufsbutton für WooCommerce ==
Contributors: entruencer
Tags: woocommerce, widerruf, withdrawal, eu, compliance
Requires at least: 6.4
Tested up to: 6.5
Requires PHP: 8.1
WC requires at least: 8.0
Stable tag: 0.2.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Open-Source-Plugin (GPL) für den ab 19.06.2026 gesetzlich verpflichtenden elektronischen Widerrufsbutton in WooCommerce. Bereitgestellt von der Entruencer UG.

== Description ==

Stellt den ab 19.06.2026 nach EU-RL 2023/2673 und Paragraf 312k BGB verpflichtenden elektronischen Widerrufsbutton für WooCommerce-Shops bereit.

Funktionsumfang:

* Zweistufiger öffentlicher Widerruf-Flow (Button -> Formular mit Name, Bestellnummer, E-Mail) via Shortcode [widerrufsbutton].
* Automatische, neutrale Eingangsbestätigung per E-Mail (gesetzliche Pflicht) mit Datum und Uhrzeit.
* Fallbasierte Vorklassifizierung A/B/C; Akzeptanz UND Ablehnung als Entwurf mit manueller 1-Klick-Freigabe (keine automatische Entscheidung).
* Benachrichtigung an den Shop-Betreiber bei jedem neuen Widerruf (mit Direktlink zur Freigabe).
* Native WooCommerce-Mails: Absender, Betreff, Texte und Layout laufen über WooCommerce -> Einstellungen -> E-Mails (kein separater Mailversand nötig).
* Automatisches Setup: legt bei Aktivierung eine Seite mit dem Shortcode an (Slug konfigurierbar).
* Produkt-Flag "Vom Widerruf ausgeschlossen" inkl. Freitext-Grund.
* Bestell-Match ohne Enumeration (Bestellnummer + E-Mail, Rate-Limiting, neutrale Antwort).
* HPOS-konform, eigene Custom Table für Widerrufe.
* White-Label: Frontend-Farben/Radius und Texte pro Shop konfigurierbar.

Frei nutzbar unter der GPL. Bereitgestellt von der Entruencer UG als Beitrag zur Community.

Haftungsausschluss: Dieses Plugin wird ohne jede Gewähr bereitgestellt. Es stellt keine Rechtsberatung dar und garantiert keine Rechtskonformität im konkreten Einzelfall. Prüfe die Eignung für deinen Shop selbst und lasse die Umsetzung im Zweifel von einer auf IT-Recht spezialisierten Stelle bestätigen. Die Nutzung erfolgt auf eigenes Risiko.

== Installation ==

1. Plugin-Verzeichnis nach wp-content/plugins/widerrufsbutton-wc/ kopieren (oder ZIP über Plugins -> Installieren hochladen).
2. Plugin im WordPress-Backend aktivieren. Dabei werden die Custom Table und automatisch eine Seite "Widerruf" mit dem Shortcode [widerrufsbutton] angelegt. Ein "composer install" ist NICHT nötig - ein Autoloader-Fallback ist eingebaut.
3. Optional: Absender, Betreff, Texte und Layout der vier Widerruf-Mails unter WooCommerce -> Einstellungen -> E-Mails anpassen. Für zuverlässige Zustellung ein SMTP-/Mailversand-Plugin nutzen.
4. Optional: Unter WooCommerce -> Widerrufe -> Einstellungen Frist, Widerruf-Seite (Slug) und Frontend-Design pflegen.

Voraussetzungen: WordPress 6.4+, PHP 8.1+, WooCommerce 8.0+ (HPOS empfohlen).

Anpassung im Detail: siehe docs/anpassung.md.

== Changelog ==

= 0.2.1 =
* Umlaute in allen nutzer-sichtbaren Strings korrigiert (Admin, Frontend, Mail-Einstellungen).

= 0.2.0 =
* Mailsystem komplett auf native WooCommerce-Mails (WC_Email) umgestellt: Absender, Betreff, Texte und Layout laufen über WooCommerce -> Einstellungen -> E-Mails.
* Neue Benachrichtigung an den Shop-Betreiber bei jedem neuen Widerruf (Empfänger konfigurierbar, Direktlink zur Freigabe).
* Auto-Setup: automatische Anlage der Widerruf-Seite mit Shortcode bei Aktivierung (Slug konfigurierbar, keine Dublette).
* Admin-Hinweise für unvollständiges Setup und fehlenden SMTP-Versand.
* Eigene Mail-Settings (Absender/Betreff/Texte) und Markenfelder entfernt - schlanker und einfacher einzurichten.

= 0.1.0 =
* Erste funktionale Implementierung: Frontend-Flow, Eingangsbestätigung, Custom-Table-Persistenz, Admin-Liste/Detail mit 1-Klick-Freigabe, White-Label-Settings, Produkt-Flag, Autoloader-Fallback.

= 0.1.0-dev =
* Projekt-Scaffold angelegt (Doku + Plugin-Skeleton, noch keine fachliche Implementierung).
