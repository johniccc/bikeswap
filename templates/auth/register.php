<div class="auth-page">
    <div class="auth-form">
        <h1>Registrace</h1>

        <?php if (isset($session) && $session->hasFlash('error')): ?>
            <div class="alert alert-error">
                <?= e($session->getFlash('error')) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($session) && $session->hasFlash('success')): ?>
            <div class="alert alert-success">
                <?= e($session->getFlash('success')) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/register">
            <input type="hidden" name="_csrf" value="<?= e($csrf ?? '') ?>">

            <div class="form-group">
                <label for="name">Jméno a příjmení</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="phone">Telefon (nepovinné)</label>
                <input type="tel" id="phone" name="phone">
            </div>

            <div class="form-group">
                <label for="password">Heslo (min. 8 znaků)</label>
                <input type="password" id="password" name="password" minlength="8" required>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Potvrzení hesla</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>

            <button type="submit" class="btn btn-primary">Zaregistrovat se</button>

            <div class="form-footer">
                <p>Už máte účet? <a href="/login">Přihlaste se</a></p>
            </div>
        </form>
    </div>
</div>