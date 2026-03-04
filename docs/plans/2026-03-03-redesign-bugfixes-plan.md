# BikeSwap — Redesign + Bug Fixes Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Kompletní frontend redesign (mobile-first, cyklistická estetika, bottom nav + desktop sidebar) + všechny bug fixy z README.

**Architecture:** Vanilla CSS přepis + PHP šablony upraveny pro nový layout. Nové funkce přidány do stávajícího MVC frameworku beze změny závislostí (kromě jsQR přes CDN).

**Tech Stack:** PHP 8.3 custom MVC, Vanilla CSS, Vanilla JS, jsQR (CDN), MySQL, Docker

**Security note:** Chat messages are always escaped via PHP `e()` before JSON serialisation. In JS use `textContent` — never `innerHTML` with server data.

---

## Pořadí implementace (23 tasků)

```
1.  CSS redesign
2.  Layout main.php (mobile bottom nav + desktop sidebar)
3.  Zprávy přihlášeného vpravo
4.  Hvězdičky zleva doprava
5.  Datum formát dd/mm/yyyy
6.  QR download + print tlačítka
7.  Validace data krádeže
8.  Skrýt rezervaci pro vlastníka kola
9.  Opravit duplicitní akce vlastníka (zamítnout vs. zavřít)
10. QR scanner modal (kamera + fallback)
11. Availability badge na listech kol
12. Blokovat duplicitní žádost o rezervaci
13. Karma penalta za pozdní zrušení
14. Uživatelský profil (/profile, /profile/settings)
15. Redirect po přihlášení/registraci (?redirect=)
16. Realtime chat (long polling 3s)
17. Dynamická správa fotek ve formuláři
18. Flow nevrácení kola (disputed stav)
19. Ověření karmy pro nálezy
20. Konzistence identit v konverzacích
21. Uzavření konverzace u nálezu
22. Turnstile server-side ověření
23. Číslo policejního případu
```

---

## FÁZE 1: CSS + Layout

### Task 1: CSS Design System

**Files:**
- Modify: `public/css/style.css` (full rewrite)

Přepsat celý soubor. Klíčové sekce:

**CSS Variables:**
```
--color-primary:       #2D7A4F
--color-primary-hover: #235f3d
--color-accent:        #4CAF7D
--color-bg:            #F4F7F4
--color-surface:       #FFFFFF
--color-surface-2:     #EEF3EE
--color-border:        #D1DDD1
--color-text:          #1A2E1A
--color-text-muted:    #5A6E5A
--color-danger:        #DC2626
--color-warning:       #D97706
--color-success:       #16A34A
--sidebar-width:       220px
--bottom-nav-height:   64px
--top-bar-height:      52px
```

**Mobile layout (default):**
- `.top-bar` — fixed 52px, zelená, logo + notif bell
- `.page-content` — padding-top: top-bar + 1rem, padding-bottom: bottom-nav + 1rem
- `.bottom-nav` — fixed bottom 64px, 5 položek
- `.bottom-nav-qr-btn` — 52px kruh, zelený, position bottom: 14px (přečnívá)

**Desktop (@media min-width: 768px):**
- `.top-bar` a `.bottom-nav` → `display: none`
- `.sidebar` — fixed left, 220px, zelená, flex column
- `.page-content` — margin-left: 220px

**Zprávy v chatu:**
```css
.message { align-self: flex-start; }
.message.mine { align-self: flex-end; }
.message.mine .message-bubble { background: var(--color-primary); color: #fff; }
.message-bubble { white-space: pre-wrap; } /* renders newlines safely via textContent */
```

**Hvězdičky:**
```css
.star-rating { display: flex; flex-direction: row; } /* leva → prava */
```

**Commit:**
```bash
git add public/css/style.css
git commit -m "feat: redesign CSS with green cycling theme, mobile-first design system"
```

---

### Task 2: Layout Template

**Files:**
- Modify: `templates/layouts/main.php`
- Modify: `src/Helpers/functions.php`

**Step 1:** Přidat do `functions.php`:
```php
function isActiveRoute(string $path): string {
    $current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    if ($path === '/' && $current === '/') return 'active';
    if ($path !== '/' && str_starts_with($current, $path)) return 'active';
    return '';
}
```

**Step 2:** Nahradit `main.php` strukturou:
```
<div class="app-shell">
  <header class="top-bar">       ← mobile only
  <aside class="sidebar">        ← desktop only (CSS hides on mobile)
  <main class="page-content">
  <nav class="bottom-nav">       ← mobile only
</div>
<div class="modal-overlay" id="qr-modal">  ← QR scanner modal
<div class="toast-container" id="toast-container">
<script jsQR CDN>
<script src="/js/app.js">
<script> notifikační polling (30s, toast při novém) </script>
```

**Sidebar obsahuje:**
- Logo: `Bike<span>Swap</span>` (span = accent color)
- Nav links s `isActiveRoute()`
- QR tlačítko `id="open-qr-scanner"`
- Footer: profil link + logout form

**Bottom nav:**
- Domů | Veřejná kola | [QR btn `id="open-qr-scanner-mobile"`] | Sdílená kola | Profil/Login

**Notification script:**
```javascript
// textContent only — safe
t.textContent = msg;
el.textContent = count > 99 ? '99+' : String(count);
```

**Commit:**
```bash
git add templates/layouts/main.php src/Helpers/functions.php
git commit -m "feat: mobile bottom nav and desktop sidebar layout"
```

---

## FÁZE 2: Jednoduché bug fixy

### Task 3: Zprávy vpravo

**Files:** `templates/reservation/detail.php`, `templates/found/conversation.php`, `src/Controllers/ReservationController.php`

V `ReservationController::detail()` předat `'currentSenderType' => $isOwner ? 'owner' : 'borrower'`

V šablonách foreach zpráv:
```php
<?php $isMine = $message->getSenderType() === $currentSenderType; ?>
<div class="message <?= $isMine ? 'mine' : '' ?> <?= $message->getSenderType() === 'system' ? 'message-system' : '' ?>">
    <div class="message-bubble"><?= e($message->getMessage()) ?></div>
    <?php if ($message->getSenderType() !== 'system'): ?>
    <div class="message-meta"><?= e($message->getSenderLabel()) ?> · <?= formatDateTime($message->getCreatedAt()) ?></div>
    <?php endif; ?>
</div>
```

Found conversation owner: `$isMine = $message->getSenderType() === 'owner'`
Found conversation finder: `$isMine = $message->getSenderType() === 'finder'`

```bash
git commit -m "fix: own messages aligned right in conversations"
```

---

### Task 4: Hvězdičky zleva doprava

**Files:** `templates/reservation/detail.php`

HTML — zajistit pořadí 1→5:
```php
<div class="star-rating">
    <?php for ($i = 1; $i <= 5; $i++): ?>
    <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>">
    <label for="star<?= $i ?>" data-value="<?= $i ?>">☆</label>
    <?php endfor; ?>
</div>
```

JS — přidat do `app.js`:
```javascript
document.querySelectorAll('.star-rating').forEach(function(c) {
    var labels = Array.from(c.querySelectorAll('label'));
    var inputs = Array.from(c.querySelectorAll('input[type="radio"]'));
    function paint(n) {
        labels.forEach(function(l) {
            var v = parseInt(l.getAttribute('data-value'), 10);
            l.textContent = v <= n ? '★' : '☆';
            v <= n ? l.classList.add('filled') : l.classList.remove('filled');
        });
    }
    inputs.forEach(function(inp) { inp.addEventListener('change', function() { paint(parseInt(this.value,10)); }); });
    labels.forEach(function(l) { l.addEventListener('mouseover', function() { paint(parseInt(this.getAttribute('data-value'),10)); }); });
    c.addEventListener('mouseleave', function() { var ch = c.querySelector('input:checked'); paint(ch ? parseInt(ch.value,10) : 0); });
});
```

```bash
git commit -m "fix: star rating fills left-to-right"
```

---

### Task 5: Datum formát dd/mm/yyyy

**Files:** `src/Helpers/functions.php`, všechny šablony

```php
function formatDate(mixed $d): string {
    if ($d === null || $d === '') return '';
    if ($d instanceof \DateTimeInterface) return $d->format('d/m/Y');
    $ts = is_int($d) ? $d : strtotime((string)$d);
    return $ts !== false ? date('d/m/Y', $ts) : (string)$d;
}
function formatDateTime(mixed $d): string {
    if ($d === null || $d === '') return '';
    if ($d instanceof \DateTimeInterface) return $d->format('d/m/Y H:i');
    $ts = is_int($d) ? $d : strtotime((string)$d);
    return $ts !== false ? date('d/m/Y H:i', $ts) : (string)$d;
}
```

Prohledat šablony: `grep -rn "->format\|date(" templates/` — nahradit výstupy datumů těmito helpery.

```bash
git commit -m "fix: date format dd/mm/yyyy throughout app"
```

---

### Task 6: QR download + print tlačítka

**Files:** `templates/bike/public-detail.php`

Pod `<img class="qr-code-image">` přidat:
```php
<div class="flex gap-sm mt-sm" style="justify-content:center;flex-wrap:wrap;">
    <a href="/file/qr/<?= e($bike->getQrHash()) ?>"
       download="qr-kolo-<?= e($bike->getQrHash()) ?>.png"
       class="btn btn-secondary btn-sm">⬇ Stáhnout QR</a>
    <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm">🖨 Tisknout</button>
</div>
```

```bash
git commit -m "fix: add QR code download and print buttons"
```

---

### Task 7: Validace data krádeže

**Files:** `src/Controllers/TheftController.php` nebo `src/Services/TheftService.php`

V metodě `report()`, před INSERT:
```php
$bikeYear  = $bike->getYear(); // zkontrolovat název metody
$theftDate = $request->input('theft_date', '');
if ($bikeYear && $theftDate && (int)date('Y', strtotime($theftDate)) < (int)$bikeYear) {
    $this->session->flash('error', 'Datum krádeže nemůže být před rokem výroby (' . $bikeYear . ').');
    return redirect("/theft/report/{$bikeId}");
}
```

```bash
git commit -m "fix: theft date cannot be before bike manufacture year"
```

---

### Task 8: Skrýt rezervaci pro vlastníka

**Files:** `templates/bike/public-detail.php`, `templates/reservation/shared-bikes.php`

```php
<?php if ($isLoggedIn && !$isOwner && $bike->isShared() && !$bike->isStolen()): ?>
    <a href="/reservation/new/<?= $bike->getId() ?>" class="btn btn-primary">📅 Rezervovat</a>
<?php elseif (!$isLoggedIn && $bike->isShared() && !$bike->isStolen()): ?>
    <a href="/login?redirect=/bike/<?= e($bike->getQrHash()) ?>" class="btn btn-primary">🔑 Přihlásit a rezervovat</a>
<?php endif; ?>
```

```bash
git commit -m "fix: hide reservation button for bike owner"
```

---

### Task 9: Opravit akce vlastníka

**Files:** `templates/reservation/detail.php`

```php
<?php if ($isOwner): ?>
  <?php if ($reservation->isPending()): ?>
    <form method="POST" action="/reservation/<?= $reservation->getId() ?>/approve" style="display:inline">
        <input type="hidden" name="_csrf" value="<?= e($session->csrfToken()) ?>">
        <button class="btn btn-success">✓ Schválit</button>
    </form>
    <form method="POST" action="/reservation/<?= $reservation->getId() ?>/reject" style="display:inline"
          onsubmit="return confirm('Zamítnout žádost?')">
        <input type="hidden" name="_csrf" value="<?= e($session->csrfToken()) ?>">
        <button class="btn btn-danger">✕ Zamítnout</button>
    </form>
  <?php elseif ($reservation->isApproved()): ?>
    <form method="POST" action="/reservation/<?= $reservation->getId() ?>/cancel" style="display:inline"
          onsubmit="return confirm('Zrušit schválenou rezervaci?')">
        <input type="hidden" name="_csrf" value="<?= e($session->csrfToken()) ?>">
        <button class="btn btn-warning">Zrušit rezervaci</button>
    </form>
  <?php endif; ?>
<?php endif; ?>
```

```bash
git commit -m "fix: show correct owner actions per reservation status"
```

---

## FÁZE 3: QR Scanner

### Task 10: QR Scanner Modal

**Files:** `public/js/app.js`

Přidat sekci do `app.js`. Klíčové body:
- Otevřít přes `id="open-qr-scanner"` a `id="open-qr-scanner-mobile"`
- `getUserMedia({ video: { facingMode: 'environment' } })`
- Skenovat frame-by-frame přes `requestAnimationFrame`
- `window.jsQR(imageData, width, height)` pro dekódování
- `setStatus()` vždy přes `statusEl.textContent = msg` — nikdy innerHTML
- File input fallback: `FileReader` → `Image` → canvas → jsQR
- `handleCode(url)`: validovat `hostname === window.location.hostname && pathname.startsWith('/bike/')` → redirect

```javascript
// Celá implementace v app.js — klíčové bezpečnostní pravidlo:
// statusEl.textContent = msg;  // VŽDY textContent, nikdy innerHTML pro server data
```

```bash
git commit -m "feat: QR scanner modal with camera and file upload fallback"
```

---

## FÁZE 4: Backend funkce

### Task 11: Availability Badge

**Files:** `src/Repository/ReservationRepository.php`, `src/Controllers/ReservationController.php`, `templates/reservation/shared-bikes.php`

```php
// ReservationRepository:
public function findUnavailableBikeIds(): array {
    return array_column($this->db->query(
        "SELECT DISTINCT bike_id FROM reservations WHERE status IN ('approved','active') AND date_to >= CURDATE()"
    ), 'bike_id');
}
```

V `sharedBikes()`: `$unavailableIds = $this->reservationRepo->findUnavailableBikeIds();`

V šabloně:
```php
<?php $u = in_array($bike->getId(), $unavailableIds ?? []); ?>
<span class="availability-badge <?= $u ? 'availability-unavailable' : 'availability-available' ?>">
    <?= $u ? '✕ Nedostupné' : '✓ Dostupné' ?>
</span>
```

```bash
git commit -m "feat: availability badge on shared bikes list"
```

---

### Task 12: Blokovat duplicitní rezervaci

**Files:** `src/Repository/ReservationRepository.php`, `src/Services/ReservationService.php`

```php
// Repository:
public function hasPendingOverlap(int $borrowerId, int $bikeId, string $from, string $to): bool {
    return null !== $this->db->queryOne(
        "SELECT id FROM reservations WHERE borrower_id=:b AND bike_id=:bike AND status='pending'
         AND date_from<=:to AND date_to>=:from",
        ['b'=>$borrowerId,'bike'=>$bikeId,'from'=>$from,'to'=>$to]
    );
}

// Service (v create metodě):
if ($this->reservationRepo->hasPendingOverlap($borrowerId, $bikeId, $dateFrom, $dateTo)) {
    throw new \RuntimeException('Již máte nevyřízenou žádost na tento termín.');
}
```

```bash
git commit -m "fix: prevent duplicate pending reservation for same bike and dates"
```

---

### Task 13: Karma penalta za pozdní zrušení

**Files:** `src/Services/ReservationService.php`

V `cancel()`, po permission checks:
```php
if ($reservation->isApproved() || $reservation->isPending()) {
    $start = new \DateTime($reservation->getDateFrom());
    $diffH = ($start->getTimestamp() - (new \DateTime())->getTimestamp()) / 3600;
    if ($diffH > 0 && $diffH < 24) {
        $this->karmaService->addPoints($userId, -5, 'Pozdní zrušení rezervace');
    }
}
```

```bash
git commit -m "feat: karma -5 for cancellation less than 24h before start"
```

---

### Task 14: Uživatelský profil

**Files:**
- Create: `src/Controllers/ProfileController.php`
- Create: `templates/profile/index.php`
- Create: `templates/profile/settings.php`
- Modify: `config/routes.php`
- Modify: `src/Core/Container.php`
- Modify: `src/Repository/FoundReportRepository.php`

**ProfileController metody:** `index()`, `settings()`, `saveSettings()`

`index()` předá do view: `user`, `bikes`, `reservations`, `foundReports`, `karmaLabel`

Přidat do `FoundReportRepository`:
```php
public function findByReporterUserId(int $userId): array {
    return $this->db->query(
        "SELECT * FROM found_reports WHERE reported_by=:u ORDER BY created_at DESC",
        ['u'=>$userId]
    );
}
```

Routes (uvnitř auth group):
```php
$router->get('/profile', [ProfileController::class, 'index']);
$router->get('/profile/settings', [ProfileController::class, 'settings']);
$router->post('/profile/settings', [ProfileController::class, 'saveSettings']);
```

`profile/index.php` sekce: karma label, moje kola (bike-grid), moje nálezy (list s linky), moje rezervace

`profile/settings.php`: form na úpravu jména, odkaz na heslo

```bash
git commit -m "feat: user profile page with bikes, found reports and settings"
```

---

### Task 15: Redirect po přihlášení

**Files:** `src/Controllers/AuthController.php`

```php
// loginForm():
$redirect = $request->query('redirect', '');
if ($redirect && str_starts_with($redirect, '/')) {
    $this->session->set('auth_redirect', $redirect);
}

// login() — po úspěchu:
$redirect = $this->session->get('auth_redirect', '/dashboard');
$this->session->forget('auth_redirect');
return redirect($redirect);
```

Stejné pro `registerForm()` + `register()`.

V šablonách kde posíláme hosta na login přidat `?redirect=` param.

```bash
git commit -m "feat: redirect back after login/register via ?redirect= param"
```

---

### Task 16: Realtime Chat (Long Polling)

**Files:**
- Create: `src/Controllers/MessagePollController.php`
- Modify: `src/Repository/ReservationMessageRepository.php`
- Modify: `config/routes.php`
- Modify: `public/js/app.js`
- Modify: `templates/reservation/detail.php`
- Modify: `templates/found/conversation.php`

**Přidat `findAfter()` do ReservationMessageRepository:**
```php
public function findAfter(int $reservationId, int $afterId): array {
    return $this->db->query(
        "SELECT * FROM reservation_messages WHERE reservation_id=:r AND id>:a ORDER BY id ASC",
        ['r'=>$reservationId,'a'=>$afterId]
    );
}
```

**MessagePollController — `reservationMessages()`:**
- GET `/reservation/{id}/poll?after={lastId}`
- Auth check: user must be owner OR borrower
- Vrátí JSON: `{messages: [{id, text, sender, mine, label, time, system}]}`
- `text` = `$m->getMessage()` (raw, escaped v JS přes textContent)

**Routes:**
```php
$router->get('/reservation/{id}/poll', [MessagePollController::class, 'reservationMessages']);
$router->get('/found/{token}/poll',    [MessagePollController::class, 'foundMessages']);
```

**JS polling v `app.js`:**
```javascript
// Klíčové: textContent místo innerHTML
bubble.textContent = msg.text;
meta.textContent   = (msg.label || '') + ' · ' + msg.time;
// poll každé 3 sekundy
setTimeout(poll, 3000);
```

**Šablony — přidat data atributy:**
```php
<div class="conversation-messages" id="messages"
     data-poll-url="/reservation/<?= $reservation->getId() ?>/poll"
     data-last-id="<?= $lastMessageId ?? 0 ?>">
```

```bash
git commit -m "feat: realtime chat with 3s long polling, XSS-safe via textContent"
```

---

### Task 17: Dynamická správa fotek

**Files:** `templates/bike/create.php`, `templates/bike/edit.php`, `public/js/app.js`

HTML v create/edit:
```php
<input type="file" name="photos[]" id="photo-input" multiple accept="image/*" class="form-control">
<div class="photo-preview-grid" id="photo-preview"></div>
<input type="hidden" name="primary_index" id="primary-index" value="0">
```

JS v `app.js` — spravovat pole `files[]`, renderovat preview:
- Remove button: `files.splice(idx,1)`, revokovat ObjectURL, syncovat FileList přes DataTransfer
- Primary button: nastavit `primaryIdx`, aktualizovat hidden input
- Renderovat přes DOM API — `document.createElement`, `textContent`, nikdy innerHTML pro data

```bash
git commit -m "feat: dynamic photo preview with remove and primary selection in forms"
```

---

### Task 18: Flow nevrácení kola

**Files:** `src/Services/ReservationService.php`, `src/Controllers/ReservationController.php`, `templates/reservation/detail.php`, `config/routes.php`

Přidat metodu `dispute()` do `ReservationService`:
```php
public function dispute(int $id, int $borrowerId): void {
    $res = $this->reservationRepo->findById($id);
    if (!$res || $res->getBorrowerId() !== $borrowerId || $res->getStatus() !== 'not_returned') {
        throw new \RuntimeException('Nelze provést.');
    }
    $this->reservationRepo->updateStatus($id, 'disputed');
    $this->notificationService->create($res->getOwnerId(), 'Sporná výpůjčka',
        'Výpůjčník se ohradil. Případ čeká na řešení.', "/reservation/{$id}");
}
```

Route: `$router->post('/reservation/{id}/dispute', [ReservationController::class, 'dispute']);`

V `detail.php` pro `not_returned` stav + borrower:
```php
<div class="alert alert-warning">Vlastník nahlásil nevrácení. Reagujte do 48 hodin.</div>
<!-- tlačítko "Vráceno" → /complete -->
<!-- tlačítko "Nahlásit problém" → /dispute -->
```

```bash
git commit -m "feat: not-returned dispute flow with new status"
```

---

### Task 19: Karma pro nálezy

**Files:** `src/Services/KarmaService.php`, `src/Services/FoundReportService.php`

Ověřit v `recalculate()`:
```php
// found_reports submitted → +5 each
$found = $this->db->queryOne("SELECT COUNT(*) as c FROM found_reports WHERE reported_by=:u", ['u'=>$userId]);
$score += ($found['c'] ?? 0) * self::POINTS_PER_FOUND_REPORT;

// resolved found_reports → +20 each
$res = $this->db->queryOne("SELECT COUNT(*) as c FROM found_reports WHERE reported_by=:u AND status='resolved'", ['u'=>$userId]);
$score += ($res['c'] ?? 0) * self::POINTS_PER_RESOLVED;
```

Ověřit že `recalculate()` je voláno po `FoundReportService::resolve()`.

```bash
git commit -m "fix: karma recalculation includes found reports"
```

---

### Task 20: Konzistence identit

**Files:** `src/Entity/ReservationMessage.php`, `src/Entity/FoundReportMessage.php`

`getSenderLabel()` musí vracet:
- `'system'`  → `'BikeSwap'`
- `'owner'`   → jméno nebo `'Vlastník'`
- `'borrower'`→ jméno nebo `'Výpůjčník'`
- `'finder'`  → `'Nálezce'` (vždy anonymní pokud nepřihlášený)

```bash
git commit -m "fix: consistent sender labels in conversations"
```

---

### Task 21: Uzavření konverzace u nálezu

**Files:** `src/Controllers/FoundReportController.php`, `templates/found/conversation.php`, `config/routes.php`

```php
// FoundReportController:
public function closeConversation(Request $request): Response {
    $id = (int) $request->param('id');
    if (!$this->session->validateCsrf($request->input('_csrf',''))) {
        $this->session->flash('error','Neplatný token.');
        return redirect("/found/{$id}/conversation");
    }
    $user  = $this->authService->currentUser();
    $report = $this->foundReportRepo->findById($id);
    $bike  = $this->bikeRepo->findById($report?->getBikeId());
    if (!$report || !$bike || $bike->getOwnerId() !== $user->getId()) {
        throw new \RuntimeException('Neoprávněný přístup.', 403);
    }
    $this->foundReportRepo->updateStatus($id, 'closed');
    $this->session->flash('success', 'Konverzace uzavřena.');
    return redirect("/found/{$id}/conversation");
}
```

Route: `$router->post('/found/{id}/close', [FoundReportController::class, 'closeConversation']);`

```bash
git commit -m "feat: close conversation for found reports"
```

---

### Task 22: Turnstile server-side ověření

**Files:** `src/Controllers/FoundReportController.php`

Zkontrolovat `report()` metodu. Pokud chybí server-side ověření:
```php
$secret = $this->config['turnstile']['secret_key'] ?? '';
if ($secret && $currentUser === null) {
    $token = $request->input('cf-turnstile-response','');
    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>5,
        CURLOPT_POSTFIELDS=>http_build_query(['secret'=>$secret,'response'=>$token])]);
    $data = json_decode(curl_exec($ch),true) ?? [];
    curl_close($ch);
    if (!($data['success']??false)) {
        $this->session->flash('error','Ověření CAPTCHA selhalo.');
        return redirect("/found/report/{$qrHash}");
    }
}
```

```bash
git commit -m "fix: Turnstile server-side verification for anonymous found reports"
```

---

### Task 23: Číslo policejního případu

**Files:** `templates/theft/report.php`, `database/schema.sql`

Ověřit existenci sloupce v DB a pole ve formuláři.
Pokud chybí ve formuláři:
```php
<div class="form-group">
    <label for="police_case_number">Číslo policejního případu</label>
    <input type="text" name="police_case_number" id="police_case_number" class="form-control"
           value="<?= e($oldInput['police_case_number'] ?? '') ?>"
           placeholder="Např. KRPA-12345/ČJ-2026">
    <span class="form-text">Nepovinné — vyplňte pokud jste krádež nahlásili policii.</span>
</div>
```

```bash
git commit -m "fix: police case number field in theft report form"
```

---

## Verifikace po dokončení

Po každém tasku ověřit v prohlížeči. Po všech tascích:
1. Projít všechny stránky na mobilu (Chrome DevTools, 375px)
2. Projít všechny stránky na desktopu
3. Otestovat QR scanner
4. Otestovat celý rezervační flow
5. Otestovat nalezené kolo flow
6. Zkontrolovat konzoli — žádné JS chyby
