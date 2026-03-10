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

<div class="card max-w-lg mb-lg">
  <div class="card-header">
    <h3>Kontaktní údaje</h3>
  </div>
  <div class="card-body">
    <form method="POST" action="/profile/settings">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

      <div class="form-group">
        <label for="settings-name">Jméno a příjmení</label>
        <input type="text" id="settings-name" name="name" required maxlength="100"
               value="<?= e($user->getName()) ?>">
      </div>

      <div class="form-group">
        <label for="settings-phone">
          Telefon
          <span style="font-size:0.8em;font-weight:600;color:var(--warning)"> doporučeno</span>
        </label>
        <input type="tel" id="settings-phone" name="phone" maxlength="20"
               value="<?= e($user->getPhone() ?? '') ?>" placeholder="+420 ...">
        <p class="form-help text-sm mt-xs">Vidí ho jen přihlášení uživatelé. Usnadní kontakt při nálezu kola nebo výpůjčce.</p>
      </div>

      <div class="form-group">
        <label for="settings-address">Adresa <span class="text-muted" style="font-weight:400">(nepovinné)</span></label>
        <input type="text" id="settings-address" name="address" maxlength="255"
               value="<?= e($user->getAddress() ?? '') ?>" placeholder="Ulice, město">
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">
          <i data-lucide="save"></i> Uložit změny
        </button>
      </div>
    </form>
  </div>
</div>

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

<div class="card max-w-lg">
  <div class="card-body">
    <div class="info-row">
      <span class="info-label">E-mail</span>
      <span class="info-value"><?= e($user->getEmail()) ?></span>
    </div>
    <p class="text-muted text-sm mt-sm">
      <i data-lucide="info" style="width:13px;height:13px;display:inline;vertical-align:-2px"></i>
      Změna e-mailu a hesla není momentálně dostupná přes web. Kontaktujte správce.
    </p>
  </div>
</div>
