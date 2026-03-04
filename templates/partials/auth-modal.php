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
          <label for="reg-name">Jméno a příjmení</label>
          <input type="text" id="reg-name" name="name" required autocomplete="name" placeholder="Jan Novák">
        </div>

        <div class="form-group">
          <label for="reg-email">E-mail</label>
          <input type="email" id="reg-email" name="email" required autocomplete="email" placeholder="vas@email.cz">
        </div>

        <div class="form-group">
          <label for="reg-phone">Telefon <span class="text-light">(nepovinné)</span></label>
          <input type="tel" id="reg-phone" name="phone" autocomplete="tel" placeholder="+420 ...">
        </div>

        <div class="form-group">
          <label for="reg-password">Heslo</label>
          <input type="password" id="reg-password" name="password" required autocomplete="new-password" placeholder="Min. 8 znaků" minlength="8">
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
