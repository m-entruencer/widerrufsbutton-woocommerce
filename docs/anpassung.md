# Anpassung & Konfiguration

Zentrale Referenz: Was ist wo wie anpassbar, wenn das Plugin in einen neuen Shop
eingebunden wird. Drei Ebenen: (1) Einstellungen im Backend, (2) Design via CSS,
(3) Templates und Filter-Hooks fuer Entwickler.

## 1. Schnellstart pro Shop

1. Plugin-Ordner nach `wp-content/plugins/widerrufsbutton-wc/` kopieren, aktivieren
   (legt die Custom Table an). Kein `composer install` noetig - das Plugin bringt
   einen eigenen Autoloader-Fallback mit.
2. Seite anlegen (z.B. "Widerruf") und den Shortcode `[widerrufsbutton]` einfuegen.
3. Unter **WooCommerce -> Widerrufe -> Einstellungen** Frist, Absender, Mail-Texte
   und White-Label-Werte pflegen.
4. Bei ausgeschlossenen Produkten (z.B. digitaler Sofort-Download) im Produkt das
   Feld "Vom Widerruf ausgeschlossen" setzen.

## 2. Einstellungen (Backend)

Pfad: **WooCommerce -> Widerrufe -> Einstellungen**. Gespeichert in der Option
`wrb_settings` (Array).

| Einstellung | Key | Default | Wirkung |
|---|---|---|---|
| Widerrufsfrist (Tage) | `deadline_days` | 14 | Laenge der Frist fuer die Fall-Einstufung. |
| Fristbeginn | `deadline_start_basis` | `created` | `created` (Bestelldatum), `paid` (Zahlung), `completed` (Abschluss). |
| Absender-Name | `sender_name` | leer | From-Name der Mails. Leer -> WordPress-Default. |
| Absender-E-Mail | `sender_email` | leer | From-Adresse. Leer -> WordPress-Default. |
| Antwort-an | `reply_to` | leer | Reply-To-Header. |
| Betreff Eingangsbestaetigung | `subject_acknowledgement` | leer | Leer -> neutraler Standardbetreff. |
| Betreff Akzeptanz | `subject_acceptance` | leer | Leer -> Standard. |
| Betreff Ablehnung | `subject_rejection` | leer | Leer -> Standard. |
| Text Eingangsbestaetigung | `body_acknowledgement` | leer | Leer -> Template `emails/acknowledgement.php`. |
| Text Akzeptanz | `body_acceptance` | leer | Leer -> Template `emails/acceptance.php`. |
| Text Ablehnung | `body_rejection` | leer | Leer -> Template `emails/rejection.php`. |
| Oeffentliche Bestaetigung | `confirmation_message` | leer | Text nach dem Absenden. Leer -> Standardtext. |
| Markenname | `brand_name` | leer | Platzhalter `{brand_name}` in Mails. Leer -> Shop-Name. |
| Logo-URL | `brand_logo_url` | leer | Optional fuer eigene Templates. |
| Akzentfarbe | `accent_color` | leer | CSS `--wrb-accent`. Hex. |
| Hintergrundfarbe | `background_color` | leer | CSS `--wrb-bg`. Hex. |
| Textfarbe | `text_color` | leer | CSS `--wrb-text`. Hex. |
| Eckenradius | `radius` | leer | CSS `--wrb-radius`, z.B. `12px`. |
| Daten bei Deinstallation loeschen | `delete_data_on_uninstall` | aus | Nur bei aktiv werden Tabelle + Optionen bei Deinstallation entfernt. |

### Mail-Platzhalter (in den Text-Feldern nutzbar)

`{brand_name}`, `{datum}`, `{uhrzeit}`, `{reference}`, `{reason}`, `{customer_name}`,
`{order_number}`. Wird ein Text-Feld leer gelassen, greift das jeweilige
ueberschreibbare PHP-Template.

## 3. Design / White-Label (CSS)

Das Frontend nutzt CSS-Custom-Properties. Aus den Farb-/Radius-Settings wird
automatisch ein scoped Inline-Style auf `.wrb-widget` erzeugt. Wer feiner steuern
will, ueberschreibt im Theme-CSS:

```css
.wrb-widget {
  --wrb-accent: #1DA3C9;   /* Buttons / Akzent */
  --wrb-bg:     #ffffff;   /* Hintergrund */
  --wrb-text:   #1a1a1a;   /* Textfarbe */
  --wrb-radius: 12px;      /* Eckenradius */
}
```

CSS-Klassen (BEM-artig, Prefix `wrb-`): `.wrb-widget`, `.wrb-button`,
`.wrb-button--primary`, `.wrb-form`, `.wrb-form__row`, `.wrb-form__input`,
`.wrb-confirmation`. Eigenes Stylesheet im Theme laden und gezielt ueberschreiben -
die Plugin-CSS hat niedrige Spezifitaet.

## 4. Templates ueberschreiben (Theme-Override)

Plugin-Templates liegen in `templates/`. Zum Ueberschreiben die Datei unter
gleichem relativem Pfad im Theme ablegen:

```
<theme>/widerrufsbutton-wc/frontend/form.php
<theme>/widerrufsbutton-wc/emails/acknowledgement.php
<theme>/widerrufsbutton-wc/emails/acceptance.php
<theme>/widerrufsbutton-wc/emails/rejection.php
```

Im Frontend-Template verfuegbar: `$redirect`. In den Mail-Templates verfuegbar:
`$brand_name`, `$datum`, `$uhrzeit`, `$reference`, `$reason`, `$customer_name`,
`$order_number` (je nach Mail-Typ gesetzt).

## 5. Filter-Hooks (Entwickler)

| Hook | Zweck | Signatur |
|---|---|---|
| `wrb_resolve_order_by_number` | Bestellnummer -> WC_Order aufloesen (z.B. Sequential Order Numbers). | `filter($order = null, string $orderNumber): ?WC_Order` |
| `wrb_rate_limit` | Rate-Limit fuer das Submit-Formular anpassen. | `filter(['max' => 5, 'window' => 600])` |
| `wrb_email_headers` | Mail-Header ergaenzen (z.B. Bcc). | `filter(array $headers): array` |

## 6. Shortcode

`[widerrufsbutton]` - rendert Stufe 1 (Button) und Stufe 2 (Formular). Nach dem
Absenden wird die gleiche Seite mit `?wrb=ok` aufgerufen und zeigt die neutrale
Bestaetigung. Mehrfach auf einer Seite ist moeglich.

## 7. Verarbeitungslogik (Faelle A/B/C)

Die Fall-Einstufung dient nur der Vorklassifizierung und der Auswahl des passenden
Entwurfs - sie versendet NIE automatisch eine Entscheidung.

- Automatisch geht nur die neutrale **Eingangsbestaetigung** raus (gesetzliche Pflicht).
- **Akzeptanz und Ablehnung** sind Entwuerfe und werden im Backend (Detailansicht)
  per 1-Klick freigegeben und versendet.

Status-Werte: `eingegangen`, `in_bearbeitung`, `erledigt`, `abgelehnt`.

## 8. Datenspeicherung

Eigene Custom Table `{prefix}entruencer_withdrawals` (kein CPT, updatesicher).
Bestellzugriff ausschliesslich HPOS-konform via `wc_get_order()`. Schema-Version in
`wrb_schema_version`; Migrationen laufen automatisch bei `admin_init`.
