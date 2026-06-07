# Funktionale Spezifikation

Plugin: Widerrufsbutton für WooCommerce (White-Label, intern Entruencer UG).
Rechtsgrundlage: EU-RL 2023/2673, Paragraf 312k BGB, verpflichtend ab 19.06.2026.
Quelle der Pflichten: IT-Recht-Kanzlei + Stripe.

## Gesetzliche Pflichtbestandteile (Checkliste)

- [ ] Gut sichtbarer elektronischer Widerrufsbutton, leicht zugänglich.
- [ ] Zweistufiger Flow: Button -> Bestätigungsformular.
- [ ] Bestätigungsseite/-funktion zum Absenden der Widerrufserklärung.
- [ ] Automatische Eingangsbestätigung an den Verbraucher (dauerhafter Datenträger).
- [ ] Eingangsbestätigung enthält Datum UND Uhrzeit des Eingangs.
- [ ] Erklärung muss ohne Login/Account abgegeben werden können.
- [ ] Keine unnötigen Pflichtangaben über die Identifikation hinaus.

Status aller Punkte: OFFEN (Implementierung folgt nach Rechtsklaerung, siehe rechtsfragen.md).

## Zweistufiger Flow

1. Stufe 1: Button "Vertrag widerrufen" auf einer öffentlichen Seite (Shortcode [widerrufsbutton]).
2. Stufe 2: Formular mit Name, Bestellnummer, E-Mail + Button "Widerruf bestätigen".
3. Nach Absenden: immer neutrale Bestätigungsseite ("Eingang bestätigt").
4. Parallel: sofortige neutrale Eingangsbestätigung per E-Mail.

## Datenfelder (Custom Table entruencer_withdrawals)

| Feld | Zweck |
|---|---|
| id | Primärschlüssel |
| order_id | Verknüpfung zur WC-Bestellung (HPOS, via wc_get_order) |
| order_number | Eingegebene/aufgelöste Bestellnummer |
| customer_email | E-Mail des Widerrufenden |
| customer_name | Name des Widerrufenden |
| received_at_utc | Eingangszeitpunkt UTC |
| received_at_local | Eingangszeitpunkt lokal (für Bestätigung) |
| case_type | A / B / C |
| deadline_days_snapshot | Fristlänge zum Eingangszeitpunkt |
| order_date_snapshot | Fristbeginn-Datum (Snapshot) |
| excluded_flag | Position(en) vom Widerruf ausgeschlossen |
| exclusion_reason | Begründung des Ausschlusses |
| waiver_proven | Wirksamer Verzicht nachgewiesen (digital/Sofort-Download) |
| confirmation_mail_sent | Eingangsbestätigung versendet |
| status | eingegangen / in_bearbeitung / erledigt / abgelehnt |
| created_at | Datensatz-Anlage |

## Die drei Fälle A/B/C

| Fall | Bedingung | Automatik | Manuell (1-Klick-Freigabe) |
|---|---|---|---|
| A | in Frist + nicht ausgeschlossen | Eingangsbestätigung sofort | Akzeptanz-ENTWURF -> Freigabe |
| B | in Frist + ausgeschlossen | Eingangsbestätigung sofort | Ablehnungs-ENTWURF -> Freigabe |
| C | außerhalb Frist | Eingangsbestätigung sofort | Entwurf -> Prüfung/Freigabe |

Wichtig: Die neutrale Eingangsbestätigung geht in ALLEN Fällen sofort und
automatisch raus. KEINE Entscheidung (weder Akzeptanz noch Ablehnung) wird
automatisch versendet - alle Entscheidungen sind Entwürfe mit manueller
1-Klick-Freigabe im Admin. Die Fall-Einstufung dient nur der Vorklassifizierung
und der Auswahl des passenden Entwurfs.

## Mail-Pflichten

- Eingangsbestätigung ist gesetzliche Pflicht und neutral formuliert.
- Datum + Uhrzeit sind Pflichtbestandteil.
- Versand via wp_mail(); SMTP-Relay empfohlen.
- Templates als überschreibbare PHP-Templates (Theme-Override).
- Absender, Reply-To, Betreff, Texte aus den Settings (white-label).
- Versandfehler werden geloggt UND im Admin sichtbar gemacht.

## Produkt-Flag

- Produkt-Datentab-Feld "Vom Widerruf ausgeschlossen" (Checkbox) + Freitext-Grund.
- Speicherung als Produkt-Meta.
- Plugin leitet exclusion_flag pro Bestellposition ab.

## Bestell-Match ohne Enumeration

- Match nur über Kombi Bestellnummer + E-Mail.
- Rate-Limiting pro IP (und ggf. pro Bestellnummer).
- Antwort IMMER neutral, kein Hinweis ob Bestellung existiert.

## Sicherheitsanforderungen

- Nonces auf allen Formularen und Admin-Aktionen.
- Capability-Checks (manage_woocommerce) im Backend.
- Sanitize bei Eingabe, Escape bei Ausgabe.
- Honeypot-Feld gegen Spam-Bots.
- Prepared Statements für alle DB-Zugriffe.
- HPOS-konform: Bestellzugriff nur via wc_get_order()/$order->get_meta().
