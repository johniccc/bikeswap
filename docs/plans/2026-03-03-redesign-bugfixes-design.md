# BikeSwap — Redesign + Bug Fixes Design Doc
**Datum:** 2026-03-03

---

## 1. Frontend Redesign

### Estetika
- **Primární:** `#2D7A4F` (forest green)
- **Akcent:** `#4CAF7D` (světlá zelená, hover/active)
- **Pozadí:** `#F5F7F5` (světle šedozelená)
- **Povrch:** `#FFFFFF`
- **Text:** `#1A2E1A`
- **Danger:** `#DC2626`, **Warning:** `#D97706`
- Typografie: system font stack, čistý spacing

### Layout — Mobile (< 768px)
- Sticky top bar: logo + notifikační zvonek (48px)
- Scrollovatelný hlavní obsah
- Bottom navigation (64px):
  - 5 položek: Domů | Veřejná kola | **[📷 QR]** | Sdílená kola | Profil
  - QR tlačítko uprostřed: větší (56px), přečnívá nad nav, zelené kruhovité

### Layout — Desktop (≥ 768px)
- Fixní sidebar vlevo (~220px), zelený
- Scrollovatelný obsah vpravo
- Sidebar obsah (shora dolů): Logo → nav položky → QR blok (plná šířka) → Profil + notifikace
- Bottom nav skrytý

---

## 2. QR Scanner (Kamera)

- Browser `getUserMedia` API + `jsQR` (CDN) pro dekódování QR z video streamu
- Fallback: `<input type="file" accept="image/*" capture="environment">` pro nahrání foky
- Po načtení kódu: redirect na `/bike/{hash}` (stávající logika)
- Implementace: modální okno/overlay, spuštěné z QR tlačítka v nav

---

## 3. Uživatelský Profil

### Stránky
- `GET /profile` — vlastní profil (jméno, karma level, moje kola, rezervace, nalezené konverzace, recenze)
- `GET /profile/settings` — nastavení (jméno, email, heslo, notifikační preference)
- `POST /profile/settings` — uložení nastavení

### Nový Controller: `ProfileController`
Vyčlení logiku z `AuthController` a `ReservationController`.

---

## 4. Realtime Chat (Long Polling)

- Endpoint: `GET /reservation/{id}/messages?after={messageId}` — vrátí nové zprávy od daného ID
- Frontend: JS smyčka, dotaz každé 3 sekundy pokud je konverzace otevřená
- Stejný přístup pro found report konverzace: `GET /found/{token}/messages?after={messageId}`
- Zprávy přihlášeného uživatele zobrazeny vpravo (CSS: `align-self: flex-end`)

---

## 5. Bug Fixy — Detailní Přehled

### Jednoduché (CSS/šablony)
| # | Problém | Řešení |
|---|---------|--------|
| 1 | Chybí tlačítko stažení/tisk QR | Přidat `<a href="/file/qr/{hash}" download>` na detail kola |
| 2 | Zprávy přihlášeného vpravo | CSS `.message.mine { align-self: flex-end; background: #2D7A4F; }` |
| 3 | Směr hvězdiček obrácený | Opravit JS logiku — hvězdička 1 = vlevo |
| 4 | Formát data dd/mm/yyyy | Upravit všechny `date()` výpisy v šablonách |
| 5 | Krádež před výrobou kola | Validace: `theft_date >= bike.manufactured_year` |
| 6 | Vlastník vidí tlačítko rezervace | Skrýt tlačítko pokud `$bike->owner_id === $currentUser->id` |
| 7 | Vlastník má zamítnout i zavřít | Zobrazit jen jedno — zamítnout pro `pending`, zavřít pro `approved` |

### Středně složité
| # | Problém | Řešení |
|---|---------|--------|
| 8 | Dostupnost u každého kola | Přidat badge "Dostupné / Nedostupné" do bike card na základě aktivních rezervací |
| 9 | Duplicitní žádost stejný termín | Backend validace: blokovat pokud user má `pending` rezervaci na stejné bike v překrývajícím se datu |
| 10 | Karma za pozdní zrušení | KarmaService: -5 bodů pokud zrušení < 24h před termínem |
| 11 | Nalezená konverzace přes profil | Pokud `found_report.reporter_user_id IS NOT NULL`, zobrazit odkaz na `/profile` |
| 12 | Historie nálezů na profilu | `/profile` sekce "Moje nálezy" — list found_reports kde user je reporter |
| 13 | Redirect po registraci/loginu | `?redirect=/bike/{hash}` query param, uložit do session, po auth přesměrovat zpět |
| 14 | Uzavření konverzace u nálezu | Přidat tlačítko "Uzavřít konverzaci" (status → closed) pro vlastníka kola |
| 15 | Mazání souborů se smazáním kola | `BikeService::delete()` — smazat soubory z disku před DELETE z DB |
| 16 | Konzistence identit v konverzaci | Zobrazovat jméno jen pokud je user přihlášený; jinak "Anonym" |

### Větší funkce
| # | Problém | Řešení |
|---|---------|--------|
| 17 | Dynamická správa fotek ve formuláři | JS preview + možnost výběru hlavní fotky a odebrání před odesláním |
| 18 | Flow nevrácení kola | Viz sekce 6 |
| 19 | Popup nových oznámení | `Notification` browser API nebo vlastní toast při `notifications/count > 0` |
| 20 | Turnstile bot ochrana | Ověřit implementaci v `FoundReportController` a `ReservationController` |
| 21 | Karma pro nálezy | Ověřit `KarmaService::recalculate()` zahrnuje found_reports |
| 22 | Evidence policie dle čísel | Ověřit zda pole `police_case_number` je ve formuláři a DB |

---

## 6. Flow Nevrácení Kola

### Stavy
```
active → [end_date překročen] → not_returned → [48h] → lost / resolved
```

### Logika
1. Cron job (nebo lazy check při dotazu) detekuje překročení `date_to` u `active` rezervací
2. Stav → `not_returned`, notifikace borrowerovi + ownerovi
3. Borrower má 48h okno: tlačítka "Označit jako vrácené" nebo "Nahlásit problém"
4. Po 48h bez akce: stav → `lost`, kolo → `stolen`, borrower → ban
5. Pokud "Nahlásit problém": stav → `disputed`, čeká na admin review
6. Karma: -30 bodů za `lost`, -10 za `disputed` (pending resolution)

---

## 7. Tech Stack (bez změn)

- PHP 8.3, vlastní MVC
- MySQL/MariaDB
- Vanilla CSS (přepsat `style.css`)
- Vanilla JS (rozšířit `app.js`)
- `jsQR` přes CDN (nová závislost, JS only)
- Docker (beze změn)
