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

Open-Source-Plugin (GPL) fuer den ab 19.06.2026 gesetzlich verpflichtenden elektronischen Widerrufsbutton in WooCommerce. Bereitgestellt von der Entruencer UG.

== Description ==

Stellt den ab 19.06.2026 nach EU-RL 2023/2673 und Paragraf 312k BGB verpflichtenden elektronischen Widerrufsbutton fuer WooCommerce-Shops bereit.

Funktionsumfang:

* Zweistufiger oeffentlicher Widerruf-Flow (Button -> Formular mit Name, Bestellnummer, E-Mail) via Shortcode [widerrufsbutton].
* Automatische, neutrale Eingangsbestaetigung per E-Mail (gesetzliche Pflicht) mit Datum und Uhrzeit.
* Fallbasierte Vorklassifizierung A/B/C; Akzeptanz UND Ablehnung als Entwurf mit manueller 1-Klick-Freigabe (keine automatische Entscheidung).
* Benachrichtigung an den Shop-Betreiber bei jedem neuen Widerruf (mit Direktlink zur Freigabe).
* Native WooCommerce-Mails: Absender, Betreff, Texte und Layout laufen ueber WooCommerce -> Einstellungen -> E-Mails (kein separater Mailversand noetig).
* Automatisches Setup: legt bei Aktivierung eine Seite mit dem Shortcode an (Slug konfigurierbar).
* Produkt-Flag "Vom Widerruf ausgeschlossen" inkl. Freitext-Grund.
* Bestell-Match ohne Enumeration (Bestellnummer + E-Mail, Rate-Limiting, neutrale Antwort).
* HPOS-konform, eigene Custom Table fuer Widerrufe.
* White-Label: Frontend-Farben/Radius und Texte pro Shop konfigurierbar.

Frei nutzbar unter der GPL. Bereitgestellt von der Entruencer UG als Beitrag zur Community.

Haftungsausschluss: Dieses Plugin wird ohne jede Gewaehr bereitgestellt. Es stellt keine Rechtsberatung dar und garantiert keine Rechtskonformitaet im konkreten Einzelfall. Pruefe die Eignung fuer deinen Shop selbst und lasse die Umsetzung im Zweifel von einer auf IT-Recht spezialisierten Stelle bestaetigen. Die Nutzung erfolgt auf eigenes Risiko.

== Installation ==

1. Plugin-Verzeichnis nach wp-content/plugins/widerrufsbutton-wc/ kopieren (oder ZIP ueber Plugins -> Installieren hochladen).
2. Plugin im WordPress-Backend aktivieren. Dabei werden die Custom Table und automatisch eine Seite "Widerruf" mit dem Shortcode [widerrufsbutton] angelegt. Ein "composer install" ist NICHT noetig - ein Autoloader-Fallback ist eingebaut.
3. Optional: Absender, Betreff, Texte und Layout der vier Widerruf-Mails unter WooCommerce -> Einstellungen -> E-Mails anpassen. Fuer zuverlaessige Zustellung ein SMTP-/Mailversand-Plugin nutzen.
4. Optional: Unter WooCommerce -> Widerrufe -> Einstellungen Frist, Widerruf-Seite (Slug) und Frontend-Design pflegen.

Voraussetzungen: WordPress 6.4+, PHP 8.1+, WooCommerce 8.0+ (HPOS empfohlen).

Anpassung im Detail: siehe docs/anpassung.md.

== Changelog ==

= 0.2.1 =
* Umlaute in allen nutzer-sichtbaren Strings korrigiert (Admin, Frontend, Mail-Einstellungen).

= 0.2.0 =
* Mailsystem komplett auf native WooCommerce-Mails (WC_Email) umgestellt: Absender, Betreff, Texte und Layout laufen ueber WooCommerce -> Einstellungen -> E-Mails.
* Neue Benachrichtigung an den Shop-Betreiber bei jedem neuen Widerruf (Empfaenger konfigurierbar, Direktlink zur Freigabe).
* DAU-Setup: automatische Anlage der Widerruf-Seite mit Shortcode bei Aktivierung (Slug konfigurierbar, keine Dublette).
* Admin-Hinweise fuer unvollstaendiges Setup und fehlenden SMTP-Versand.
* Eigene Mail-Settings (Absender/Betreff/Texte) und Markenfelder entfernt - schlanker und DAU-tauglich.

= 0.1.0 =
* Erste funktionale Implementierung: Frontend-Flow, Eingangsbestaetigung, Custom-Table-Persistenz, Admin-Liste/Detail mit 1-Klick-Freigabe, White-Label-Settings, Produkt-Flag, Autoloader-Fallback.

= 0.1.0-dev =
* Projekt-Scaffold angelegt (Doku + Plugin-Skeleton, noch keine fachliche Implementierung).
