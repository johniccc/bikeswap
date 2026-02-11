<h1>Přihlášení</h1>

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

<form method="POST" action="/login">
    <input type="hidden" name="_csrf" value="<?= e($csrf ?? '') ?>">

    <div class="form-group">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" required>
    </div>

    <div class="form-group">
        <label for="password">Heslo</label>
        <input type="password" id="password" name="password" required>
    </div>

    <button type="submit">Přihlásit se</button>

    <p>Nemáte účet? <a href="/register">Zaregistrujte se</a></p>
</form>