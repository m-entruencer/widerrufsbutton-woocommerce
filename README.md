# Widerrufsbutton für WooCommerce

[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
![WordPress](https://img.shields.io/badge/WordPress-6.4%2B-21759b.svg)
![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0%2B-96588a.svg)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4.svg)

Kostenloses Open-Source-Plugin für den **ab 19.06.2026 gesetzlich verpflichtenden
elektronischen Widerrufsbutton** in WooCommerce (EU-Richtlinie 2023/2673,
Paragraf 312k BGB).

Ab diesem Stichtag muss jeder Online-Shop in der EU, der Verbrauchern
Vertragsabschlüsse per Button ermöglicht, auch einen ebenso einfachen
**Widerrufsbutton** anbieten. Dieses Plugin liefert genau das - sauber, schlank
und ohne Abo.

---

## Was das Plugin macht

- **Zweistufiger Widerruf-Flow** über Shortcode `[widerrufsbutton]`: Button ->
  Formular (Name, Bestellnummer, E-Mail) -> neutrale Bestätigungsseite.
- **Automatische, neutrale Eingangsbestätigung** per E-Mail mit Datum und Uhrzeit
  (gesetzliche Pflicht).
- **Keine automatische Entscheidung.** Akzeptanz und Ablehnung werden als Entwurf
  vorbereitet und im Backend per 1-Klick freigegeben. Nur die Eingangsbestätigung
  geht automatisch raus.
- **Fallbasierte Vorklassifizierung (A/B/C)** je nach Frist und Ausschluss.
- **Produkt-Flag** "Vom Widerruf ausgeschlossen" inkl. Begründung (z.B. digitale
  Sofort-Downloads).
- **Sicherer Bestell-Abgleich** ohne Enumeration (Bestellnummer + E-Mail,
  Rate-Limiting, immer neutrale Antwort).
- **HPOS-konform**, eigene Custom Table - updatesicher, kein Custom Post Type.
- **White-Label:** Farben, Texte, Absender und Mail-Inhalte komplett pro Shop
  konfigurierbar. Keine festen Markenfarben im Code.

---

## Schnellstart mit KI (empfohlen)

Du musst das nicht selbst einbauen. Gib einem KI-Coding-Assistenten wie
**Claude Code** (oder einem vergleichbaren Tool mit Zugriff auf deine
WordPress-Installation) einfach diesen Prompt:

```text
Installiere das WordPress-Plugin aus dem Repository
https://github.com/m-entruencer/widerrufsbutton-woocommerce auf meiner WordPress-Seite.
Aktiviere es, lege eine Seite "Widerruf" mit dem Shortcode [widerrufsbutton] an
und passe die Farben des Plugins (WooCommerce -> Widerrufe -> Einstellungen) an
das bestehende Design meiner Website an. Füge anschließend einen Link "Widerruf"
in den Footer ein.
```

Das war es. Der Assistent erledigt Installation, Aktivierung, Seitenanlage,
Design-Anpassung und Footer-Link in einem Rutsch. Genau so ist dieses Plugin
das erste Mal live gegangen.

> Tipp: Den Stichtag 19.06.2026 nicht verschlafen - der Einbau dauert mit KI nur
> wenige Minuten.

---

## Manuelle Installation

1. Neuestes ZIP von der [Releases-Seite](https://github.com/m-entruencer/widerrufsbutton-woocommerce/releases)
   laden (oder Repo als ZIP) und unter **Plugins -> Installieren -> Plugin hochladen**
   einspielen. Alternativ den Ordner nach `wp-content/plugins/widerrufsbutton-wc/`
   kopieren.
2. Plugin aktivieren (legt die Custom Table an). Ein `composer install` ist
   **nicht** nötig - ein Autoloader-Fallback ist eingebaut.
3. Eine Seite (z.B. "Widerruf") anlegen und den Shortcode `[widerrufsbutton]`
   einfügen.
4. Unter **WooCommerce -> Widerrufe -> Einstellungen** Frist, Absender,
   Mail-Texte und Design pflegen.

**Voraussetzungen:** WordPress 6.4+, PHP 8.1+, WooCommerce 8.0+ (HPOS empfohlen).

---

## Anpassung & White-Label

Alles ist pro Shop konfigurierbar - Farben, Eckenradius, Absender, Betreffzeilen,
Mail-Texte, Bestätigungstext. Design läuft über CSS-Custom-Properties
(`--wrb-accent`, `--wrb-bg`, `--wrb-text`, `--wrb-radius`), Templates sind im Theme
überschreibbar, für Entwickler gibt es Filter-Hooks.

Vollständige Referenz: **[docs/anpassung.md](docs/anpassung.md)**.
Technische Architektur: **[docs/architecture.md](docs/architecture.md)**.
Funktionale Spezifikation: **[docs/spec.md](docs/spec.md)**.

---

## ZIP selbst bauen

```powershell
.\build-zip.ps1
```

Erzeugt `widerrufsbutton-wc-<version>.zip` mit korrekten Forward-Slash-Pfaden
(linuxtauglich, im Gegensatz zu `Compress-Archive`).

---

## Haftungsausschluss

Dieses Plugin wird **ohne jede Gewähr** bereitgestellt (siehe GPL-Lizenz). Es
stellt **keine Rechtsberatung** dar und garantiert **keine Rechtskonformität** im
konkreten Einzelfall. Ob und wie der Widerrufsbutton für deinen Shop umzusetzen
ist, hängt von deinem konkreten Geschäftsmodell ab. Prüfe die Eignung selbst und
lass die Umsetzung im Zweifel von einer auf IT-Recht spezialisierten Stelle
bestätigen. Die Nutzung erfolgt auf eigenes Risiko.

---

## Mitwirken

Issues und Pull Requests sind willkommen. Das Plugin ist bewusst schlank gehalten -
Beiträge, die es einfach und rechtssicher halten, sind besonders gern gesehen.

---

## Lizenz & Credits

GPL-2.0-or-later. Bereitgestellt von der **[Entruencer UG (haftungsbeschränkt)](https://entruencer.de/)**
als Beitrag zur WooCommerce-Community.
