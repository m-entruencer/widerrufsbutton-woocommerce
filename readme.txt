== Widerrufsbutton für WooCommerce ==
Contributors: entruencer
Tags: woocommerce, widerruf, withdrawal, eu, compliance
Requires at least: 6.4
Tested up to: 6.5
Requires PHP: 8.1
WC requires at least: 8.0
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Open-Source-Plugin (GPL) fuer den ab 19.06.2026 gesetzlich verpflichtenden elektronischen Widerrufsbutton in WooCommerce. Bereitgestellt von der Entruencer UG.

== Description ==

Stellt den ab 19.06.2026 nach EU-RL 2023/2673 und Paragraf 312k BGB verpflichtenden elektronischen Widerrufsbutton fuer WooCommerce-Shops bereit.

Funktionsumfang:

* Zweistufiger oeffentlicher Widerruf-Flow (Button -> Formular mit Name, Bestellnummer, E-Mail) via Shortcode [widerrufsbutton].
* Automatische, neutrale Eingangsbestaetigung per E-Mail (gesetzliche Pflicht) mit Datum und Uhrzeit.
* Fallbasierte Vorklassifizierung A/B/C; Akzeptanz UND Ablehnung als Entwurf mit manueller 1-Klick-Freigabe (keine automatische Entscheidung).
* Produkt-Flag "Vom Widerruf ausgeschlossen" inkl. Freitext-Grund.
* Bestell-Match ohne Enumeration (Bestellnummer + E-Mail, Rate-Limiting, neutrale Antwort).
* HPOS-konform, eigene Custom Table fuer Widerrufe.
* White-Label: alle Marken-, Mail- und Textwerte sowie Farben/Radius pro Shop konfigurierbar.

Frei nutzbar unter der GPL. Bereitgestellt von der Entruencer UG als Beitrag zur Community.

Haftungsausschluss: Dieses Plugin wird ohne jede Gewaehr bereitgestellt. Es stellt keine Rechtsberatung dar und garantiert keine Rechtskonformitaet im konkreten Einzelfall. Pruefe die Eignung fuer deinen Shop selbst und lasse die Umsetzung im Zweifel von einer auf IT-Recht spezialisierten Stelle bestaetigen. Die Nutzung erfolgt auf eigenes Risiko.

== Installation ==

1. Plugin-Verzeichnis nach wp-content/plugins/widerrufsbutton-wc/ kopieren (oder ZIP ueber Plugins -> Installieren hochladen).
2. Plugin im WordPress-Backend aktivieren (legt die Custom Table an). Ein "composer install" ist NICHT noetig - ein Autoloader-Fallback ist eingebaut.
3. Seite anlegen und [widerrufsbutton] einfuegen.
4. Unter WooCommerce -> Widerrufe die Einstellungen (Frist, Absender, Mail-Texte, White-Label) pflegen.

Voraussetzungen: WordPress 6.4+, PHP 8.1+, WooCommerce 8.0+ (HPOS empfohlen).

Anpassung im Detail: siehe docs/anpassung.md.

== Changelog ==

= 0.1.0 =
* Erste funktionale Implementierung: Frontend-Flow, Eingangsbestaetigung, Custom-Table-Persistenz, Admin-Liste/Detail mit 1-Klick-Freigabe, White-Label-Settings, Produkt-Flag, Autoloader-Fallback.

= 0.1.0-dev =
* Projekt-Scaffold angelegt (Doku + Plugin-Skeleton, noch keine fachliche Implementierung).
