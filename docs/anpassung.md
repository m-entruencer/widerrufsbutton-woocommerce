# Anpassung & Konfiguration

Zentrale Referenz: Was ist wo wie anpassbar, wenn das Plugin in einen neuen Shop
eingebunden wird. Drei Ebenen: (1) Einstellungen im Backend, (2) Design via CSS,
(3) Templates und Filter-Hooks fuer Entwickler.

## 1. Schnellstart pro Shop

1. Plugin-Ordner nach `wp-content/plugins/widerrufsbutton-wc/` kopieren, aktivieren
   (legt die Custom Table an). Kein `composer install` noetig - das Plugin bringt
   einen eigenen Autoloader-Fallback mit.
2. Seite anlegen (z.B. "Widerruf") und den Shortcode `[widerrufsbutton]` einfuegen.
3. Mails (Absender, Betreff, Texte, Layout) laufen ueber **WooCommerce -> Einstellungen
   -> E-Mails** (vier Widerruf-Mails). Frist, Widerruf-Seite und Frontend-Design unter
   **WooCommerce -> Widerrufe -> Einstellungen**.
4. Bei ausgeschlossenen Produkten (z.B. digitaler Sofort-Download) im Produkt das
   Feld "Vom Widerruf ausgeschlossen" setzen.

## 2. Einstellungen (Backend)

Pfad: **WooCommerce -> Widerrufe -> Einstellungen**. Gespeichert in der Option
`wrb_settings` (Array).

| Einstellung | Key | Default | Wirkung |
|---|---|---|---|
| Widerrufsfrist (Tage) | `deadline_days` | 14 | Laenge der Frist fuer die Fall-Einstufung. |
| Fristbeginn | `deadline_start_basis` | `created` | `created` (Bestelldatum), `paid` (Zahlung), `completed` (Abschluss). |
| Seiten-Slug | `withdrawal_page_slug` | `widerruf` | Slug der automatisch angelegten Widerruf-Seite (nur beim Anlegen genutzt). |
| Zugeordnete Seite | `withdrawal_page_id` | 0 | Seite mit dem Shortcode. Auto-Setup setzt das automatisch. |
| Oeffentliche Bestaetigung | `confirmation_message` | leer | Text nach dem Absenden (Frontend). Leer -> Standardtext. |
| Akzentfarbe | `accent_color` | leer | CSS `--wrb-accent`. Hex. |
| Hintergrundfarbe | `background_color` | leer | CSS `--wrb-bg`. Hex. |
| Textfarbe | `text_color` | leer | CSS `--wrb-text`. Hex. |
| Eckenradius | `radius` | leer | CSS `--wrb-radius`, z.B. `12px`. |
| Daten bei Deinstallation loeschen | `delete_data_on_uninstall` | aus | Nur bei aktiv werden Tabelle + Optionen bei Deinstallation entfernt. |

### Mails (WooCommerce)

Absender, Betreff, Ueberschrift, Texte und Layout der vier Widerruf-Mails
(Eingangsbestaetigung, Akzeptanz, Ablehnung, Betreiber-Benachrichtigung) werden unter
**WooCommerce -> Einstellungen -> E-Mails** verwaltet - dieselbe Stelle wie die
Bestellmails. Bei der Betreiber-Benachrichtigung ist dort zusaetzlich der Empfaenger
einstellbar (Default: Admin-E-Mail). Platzhalter in Betreff/Ueberschrift: `{site_title}`,
`{reference}`, `{customer_name}`, `{order_number}`.

Hinweis: Fuer zuverlaessige Zustellung (gesetzliche Eingangsbestaetigung) ein
SMTP-/Mailversand-Plugin nutzen - WooCommerce/WordPress versendet sonst ueber die
Server-Standardfunktion, was im Spam landen kann.

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

Das Frontend-Template liegt unter dem Plugin-Pfad, die Mail-Templates folgen der
WooCommerce-Konvention (`<theme>/woocommerce/emails/...`):

```
<theme>/widerrufsbutton-wc/frontend/form.php
<theme>/woocommerce/emails/customer-acknowledgement.php
<theme>/woocommerce/emails/customer-acceptance.php
<theme>/woocommerce/emails/customer-rejection.php
<theme>/woocommerce/emails/admin-new-withdrawal.php
(jeweils Plain-Text-Variante unter <theme>/woocommerce/emails/plain/...)
```

Im Frontend-Template verfuegbar: `$redirect`. In den Mail-Templates verfuegbar:
`$datum`, `$uhrzeit`, `$reference`, `$customer_name`, `$order_number`, `$reason`,
`$case_type`, `$detail_url` (je nach Mail-Typ) sowie `$email_heading` und `$email`
(WC_Email-Instanz).

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
