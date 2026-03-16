<div class="page-header">
  <h1>Nastavení profilu</h1>
  <div class="page-header-actions">
    <a href="/profile" class="btn btn-ghost btn-sm">
      <i data-lucide="arrow-left"></i> Zpět na profil
    </a>
  </div>
</div>

<?php if (!$user->getPhone()): ?>
  <div class="alert alert-warning mb-lg">
    <i data-lucide="phone-missed"></i>
    <div>
      <strong>Nemáte vyplněné telefonní číslo.</strong>
      <p class="mt-xs">Doplňte ho níže — usnadní kontakt při nálezu kola nebo výpůjčce a umožní ostatním vás rychleji zastihnout.</p>
    </div>
  </div>
<?php endif; ?>

<!-- Kontaktní údaje -->
<div class="card max-w-lg mb-lg">
  <div class="card-header">
    <h3>Kontaktní údaje</h3>
  </div>
  <div class="card-body">
    <form method="POST" action="/profile/settings">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

      <div class="form-group">
        <label for="settings-first-name">Jméno</label>
        <input type="text" id="settings-first-name" name="first_name" required maxlength="50"
               value="<?= e(old('first_name', $user->getFirstName())) ?>" autocomplete="given-name">
      </div>

      <div class="form-group">
        <label for="settings-surname">Příjmení</label>
        <input type="text" id="settings-surname" name="surname" required maxlength="50"
               value="<?= e(old('surname', $user->getSurname())) ?>" autocomplete="family-name">
      </div>

      <div class="form-group">
        <label for="settings-phone">
          Telefon
          <span style="font-size:0.8em;font-weight:600;color:var(--warning)"> doporučeno</span>
        </label>
        <input type="tel" id="settings-phone" name="phone" maxlength="20"
               value="<?= e(preg_replace('/^(\+420|00420)(\d{3})(\d{3})(\d{3})$/', '$1 $2 $3 $4', old('phone', $user->getPhone() ?? ''))) ?>"
               placeholder="+420 123 456 789" pattern="(\+420|00420)\s?\d{3}\s?\d{3}\s?\d{3}" inputmode="tel">
        <p class="form-help text-sm mt-xs">Vidí ho jen přihlášení uživatelé. Usnadní kontakt při nálezu kola nebo výpůjčce.</p>
      </div>

      <div class="form-group">
        <label for="settings-address">Adresa <span class="text-muted" style="font-weight:400">(nepovinné)</span></label>
        <div class="address-autocomplete-wrapper">
          <input type="text" id="settings-address" name="address" maxlength="255"
                 value="<?= e(old('address', $user->getAddress() ?? '')) ?>" placeholder="Ulice, město"
                 data-address-autocomplete
                 data-lat-input="settings_address_lat"
                 data-lng-input="settings_address_lng"
                 data-address-validated="settings_address_validated"
                 autocomplete="off">
        </div>
        <input type="hidden" id="settings_address_lat" name="address_lat">
        <input type="hidden" id="settings_address_lng" name="address_lng">
        <input type="hidden" id="settings_address_validated" name="address_validated" value="<?= e(old('address', $user->getAddress() ?? '')) !== '' ? '1' : '0' ?>">
      </div>

      <div class="form-group geo-row">
        <button type="button" class="btn btn-ghost btn-sm" data-geolocate
                data-lat-input="settings_address_lat" data-lng-input="settings_address_lng"
                data-text-input="settings-address" data-validated-input="settings_address_validated">
          <i data-lucide="map-pin"></i> Zjistit moji polohu
        </button>
        <small class="geo-status text-muted text-sm"></small>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">
          <i data-lucide="save"></i> Uložit změny
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Změna e-mailu -->
<div class="card max-w-lg mb-lg" id="email">
  <div class="card-header">
    <h3>Změna e-mailu</h3>
  </div>
  <div class="card-body">
    <div class="info-row mb-md">
      <span class="info-label">Aktuální e-mail</span>
      <span class="info-value"><?= e($user->getEmail()) ?></span>
    </div>
    <form method="POST" action="/profile/settings/email">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

      <div class="form-group">
        <label for="settings-new-email">Nový e-mail</label>
        <input type="email" id="settings-new-email" name="new_email" required maxlength="255"
               value="<?= e(old('new_email', '')) ?>" autocomplete="email" placeholder="novy@email.cz">
      </div>

      <div class="form-group">
        <label for="settings-email-password">Potvrďte heslem</label>
        <input type="password" id="settings-email-password" name="email_current_password" required autocomplete="current-password">
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">
          <i data-lucide="mail"></i> Změnit e-mail
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Změna hesla -->
<div class="card max-w-lg mb-lg">
  <div class="card-header">
    <h3>Změna hesla</h3>
  </div>
  <div class="card-body">
    <form method="POST" action="/profile/settings/password">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

      <div class="form-group">
        <label for="settings-current-password">Aktuální heslo</label>
        <input type="password" id="settings-current-password" name="current_password" required autocomplete="current-password">
      </div>

      <div class="form-group">
        <label for="settings-new-password">Nové heslo</label>
        <input type="password" id="settings-new-password" name="new_password" required autocomplete="new-password" placeholder="Min. 8 znaků">
        <div class="password-requirements mt-xs" id="pwd-requirements-settings">
          <div class="pwd-req" id="settings-req-length"> Min. 8 znaků</div>
          <div class="pwd-req" id="settings-req-upper"> Alespoň 1 velké písmeno</div>
          <div class="pwd-req" id="settings-req-number"> Alespoň 1 číslo</div>
        </div>
      </div>

      <div class="form-group">
        <label for="settings-new-password-confirm">Nové heslo znovu</label>
        <input type="password" id="settings-new-password-confirm" name="new_password_confirmation" required autocomplete="new-password">
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">
          <i data-lucide="lock"></i> Změnit heslo
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Dvoufaktorové ověření -->
<div class="card max-w-lg mb-lg">
  <div class="card-header">
    <h3 style="display:flex;align-items:center;gap:0.5rem;">
      <?php if ($twoFactorEnabled): ?>
        <i data-lucide="shield-check"></i> Dvoufaktorové ověření
        <span class="status-badge status-badge-success" style="margin-left:auto;">Aktivní</span>
      <?php else: ?>
        <i data-lucide="shield"></i> Dvoufaktorové ověření
      <?php endif; ?>
    </h3>
  </div>
  <div class="card-body">
    <?php if ($twoFactorEnabled): ?>
      <div class="info-row mb-md">
        <span class="info-label">Záložní kódy</span>
        <span class="info-value"><?= e($recoveryCodesRemaining) ?> z 8 zbývajících</span>
      </div>
      <div class="form-actions">
        <form method="POST" action="/profile/2fa/regenerate-codes" style="display:inline;">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
          <button type="submit" class="btn btn-secondary btn-sm">
            <i data-lucide="refresh-cw"></i> Obnovit záložní kódy
          </button>
        </form>
        <a href="/profile/2fa/disable" class="btn btn-ghost btn-danger btn-sm">
          <i data-lucide="shield-off"></i> Deaktivovat
        </a>
      </div>
    <?php else: ?>
      <p class="text-muted text-sm mb-md">
        Přidejte další vrstvu zabezpečení k vašemu účtu. Po aktivaci budete při přihlášení potřebovat kód z ověřovací aplikace.
      </p>
      <a href="/profile/2fa/setup" class="btn btn-primary">
        <i data-lucide="shield-check"></i> Aktivovat 2FA
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- E-mailová upozornění -->
<div class="card max-w-lg mb-lg">
  <div class="card-header">
    <h3>E-mailová upozornění</h3>
  </div>
  <div class="card-body">
    <p class="text-muted text-sm mb-md">Vyberte, o jakých událostech chcete být informováni e-mailem.</p>
    <form method="POST" action="/profile/settings/preferences">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

      <div class="pref-list">
        <label class="pref-row">
          <div class="pref-info">
            <span class="pref-title">Nález kola</span>
            <span class="pref-desc text-muted text-sm">Někdo nahlásí nález vašeho kola</span>
          </div>
          <input type="checkbox" name="email_on_found_report" class="pref-toggle"
                 <?= $preferences->isEmailOnFoundReport() ? 'checked' : '' ?>>
        </label>

        <label class="pref-row">
          <div class="pref-info">
            <span class="pref-title">Nová rezervace</span>
            <span class="pref-desc text-muted text-sm">Někdo požádá o výpůjčku vašeho kola</span>
          </div>
          <input type="checkbox" name="email_on_reservation" class="pref-toggle"
                 <?= $preferences->isEmailOnReservation() ? 'checked' : '' ?>>
        </label>

        <label class="pref-row">
          <div class="pref-info">
            <span class="pref-title">Nová zpráva</span>
            <span class="pref-desc text-muted text-sm">Nová zpráva v konverzaci (rezervace nebo nález)</span>
          </div>
          <input type="checkbox" name="email_on_message" class="pref-toggle"
                 <?= $preferences->isEmailOnMessage() ? 'checked' : '' ?>>
        </label>

        <label class="pref-row">
          <div class="pref-info">
            <span class="pref-title">Změna stavu výpůjčky</span>
            <span class="pref-desc text-muted text-sm">Schválení, zamítnutí, dokončení a další změny stavu</span>
          </div>
          <input type="checkbox" name="email_on_status_change" class="pref-toggle"
                 <?= $preferences->isEmailOnStatusChange() ? 'checked' : '' ?>>
        </label>
      </div>

      <div class="form-actions mt-md">
        <button type="submit" class="btn btn-primary">
          <i data-lucide="save"></i> Uložit předvolby
        </button>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  // Password requirements indicator
  var pwd = document.getElementById('settings-new-password');
  if (pwd) {
    var reqLength = document.getElementById('settings-req-length');
    var reqUpper  = document.getElementById('settings-req-upper');
    var reqNumber = document.getElementById('settings-req-number');
    pwd.addEventListener('input', function () {
      var v = pwd.value;
      reqLength.classList.toggle('met', v.length >= 8);
      reqUpper.classList.toggle('met', /[A-Z]/.test(v));
      reqNumber.classList.toggle('met', /[0-9]/.test(v));
    });
  }

  // Phone auto-format: +420 XXX XXX XXX
  function formatCzechPhone(input) {
    var raw = input.value.replace(/[^\d+]/g, '');
    var prefix = '', digits = '';

    if (raw.startsWith('+420')) {
      prefix = '+420'; digits = raw.slice(4);
    } else if (raw.startsWith('00420')) {
      prefix = '00420'; digits = raw.slice(5);
    } else {
      return;
    }

    digits = digits.slice(0, 9);
    var formatted = prefix;
    if (digits.length > 0) formatted += ' ' + digits.slice(0, 3);
    if (digits.length > 3) formatted += ' ' + digits.slice(3, 6);
    if (digits.length > 6) formatted += ' ' + digits.slice(6, 9);
    input.value = formatted;
  }

  var phoneInput = document.getElementById('settings-phone');
  var settingsForm = phoneInput ? phoneInput.closest('form') : null;

  if (phoneInput) {
    phoneInput.addEventListener('input', function () { formatCzechPhone(phoneInput); });
  }
  if (settingsForm) {
    settingsForm.addEventListener('submit', function () {
      phoneInput.value = phoneInput.value.replace(/\s+/g, '');
    });
  }
}());
</script>
