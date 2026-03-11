<div class="modal-overlay auth-modal" id="auth-modal">
  <div class="modal" style="max-width:420px">
    <div class="modal-header">
      <h2 class="modal-title" id="auth-modal-title">Přihlášení</h2>
      <button type="button" class="modal-close" id="close-auth-modal" aria-label="Zavřít">
        <i data-lucide="x"></i>
      </button>
    </div>
    <div class="modal-body">
      <!-- Tabs -->
      <div class="auth-tabs">
        <button type="button" class="auth-tab active" data-tab="login">Přihlášení</button>
        <button type="button" class="auth-tab" data-tab="register">Registrace</button>
      </div>

      <!-- Registration success message (shown inside modal) -->
      <?php if (isset($session) && ($regSuccess = $session->getFlash('registration_success'))): ?>
        <div class="alert alert-success mb-md" id="auth-reg-success">
          <i data-lucide="check-circle"></i> <?= e($regSuccess) ?>
        </div>
      <?php endif; ?>

      <!-- Login Form -->
      <form method="POST" action="/login" class="auth-form active" id="auth-form-login">
        <?php if (isset($session)): ?>
          <input type="hidden" name="_csrf" value="<?= e($session->csrfToken()) ?>">
        <?php endif; ?>

        <div class="form-group">
          <label for="login-email">E-mail</label>
          <input type="email" id="login-email" name="email" required autocomplete="email" placeholder="vas@email.cz">
        </div>

        <div class="form-group">
          <label for="login-password">Heslo</label>
          <input type="password" id="login-password" name="password" required autocomplete="current-password" placeholder="Vaše heslo">
          <div style="text-align:right;margin-top:0.35rem">
            <a href="/forgot-password" style="font-size:0.8em;color:var(--text-muted)">Zapomněli jste heslo?</a>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg">
          <i data-lucide="log-in"></i> Přihlásit se
        </button>

        <div class="auth-switch">
          Nemáte účet? <button type="button" class="auth-switch-btn" data-tab="register">Zaregistrujte se</button>
        </div>
      </form>

      <!-- Register Form -->
      <form method="POST" action="/register" class="auth-form" id="auth-form-register">
        <?php if (isset($session)): ?>
          <input type="hidden" name="_csrf" value="<?= e($session->csrfToken()) ?>">
        <?php endif; ?>

        <div class="form-group">
          <label for="reg-first-name">Jméno</label>
          <input type="text" id="reg-first-name" name="first_name" required autocomplete="given-name" placeholder="Jan" value="<?= e(old('first_name')) ?>">
        </div>

        <div class="form-group">
          <label for="reg-surname">Příjmení</label>
          <input type="text" id="reg-surname" name="surname" required autocomplete="family-name" placeholder="Novák" value="<?= e(old('surname')) ?>">
        </div>

        <div class="form-group">
          <label for="reg-email">E-mail</label>
          <input type="email" id="reg-email" name="email" required autocomplete="email" placeholder="vas@email.cz" value="<?= e(old('email')) ?>">
        </div>

        <div class="form-group">
          <label for="reg-phone">
            Telefon
            <span class="text-muted" style="font-weight:400"> (nepovinné, </span><span style="font-size:0.85em;font-weight:600;color:var(--warning)">doporučeno</span><span class="text-muted" style="font-weight:400">)</span>
          </label>
          <input type="tel" id="reg-phone" name="phone" autocomplete="tel" placeholder="+420 123 456 789"
                 pattern="(\+420|00420)\s?\d{3}\s?\d{3}\s?\d{3}" inputmode="tel" value="<?= e(old('phone')) ?>">
          <p class="form-help text-sm mt-xs">
            <i data-lucide="info" style="width:13px;height:13px;display:inline;vertical-align:-2px"></i>
            Umožní rychlejší kontakt při nálezu kola nebo výpůjčce. Vidí ho jen přihlášení uživatelé.
          </p>
        </div>

        <div class="form-group">
          <label for="reg-password">Heslo</label>
          <input type="password" id="reg-password" name="password" required autocomplete="new-password" placeholder="Min. 8 znaků">
          <div class="password-requirements mt-xs" id="pwd-requirements">
            <div class="pwd-req" id="req-length"> Min. 8 znaků</div>
            <div class="pwd-req" id="req-upper"> Alespoň 1 velké písmeno</div>
            <div class="pwd-req" id="req-number"> Alespoň 1 číslo</div>
          </div>
        </div>

        <div class="form-group">
          <label for="reg-password-confirm">Heslo znovu</label>
          <input type="password" id="reg-password-confirm" name="password_confirmation" required autocomplete="new-password" placeholder="Zopakujte heslo">
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg">
          <i data-lucide="user-plus"></i> Vytvořit účet
        </button>

        <div class="auth-switch">
          Už máte účet? <button type="button" class="auth-switch-btn" data-tab="login">Přihlaste se</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function () {
  function formatCzechPhone(input) {
    var raw = input.value.replace(/[^\d+]/g, '');
    var prefix = '', digits = '';

    if (raw.startsWith('+420')) {
      prefix = '+420'; digits = raw.slice(4);
    } else if (raw.startsWith('00420')) {
      prefix = '00420'; digits = raw.slice(5);
    } else {
      // User is still typing the prefix — don't reformat yet
      return;
    }

    digits = digits.slice(0, 9);
    var formatted = prefix;
    if (digits.length > 0) formatted += ' ' + digits.slice(0, 3);
    if (digits.length > 3) formatted += ' ' + digits.slice(3, 6);
    if (digits.length > 6) formatted += ' ' + digits.slice(6, 9);
    input.value = formatted;
  }

  function stripSpacesBeforeSubmit(form) {
    var phone = form.querySelector('input[name="phone"]');
    if (phone) phone.value = phone.value.replace(/\s+/g, '');
  }

  document.addEventListener('DOMContentLoaded', function () {
    var regPhone = document.getElementById('reg-phone');
    var regForm  = document.getElementById('auth-form-register');

    if (regPhone) {
      regPhone.addEventListener('input', function () { formatCzechPhone(regPhone); });
    }
    if (regForm) {
      regForm.addEventListener('submit', function () { stripSpacesBeforeSubmit(regForm); });
    }
  });
}());
</script>
