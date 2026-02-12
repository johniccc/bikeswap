<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'BikeSwap') ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header>
        <nav>
            <a href="/" class="nav-brand">BikeSwap</a>

            <div class="nav-links">
                <a href="/stolen">Odcizená kola</a>

                <?php if (isset($session) && $session->isLoggedIn()): ?>
                    <a href="/dashboard">Moje kola</a>
                    <a href="/bike/new">Registrovat kolo</a>
                    <form method="POST" action="/logout" class="nav-logout">
                        <input type="hidden" name="_csrf" value="<?= e($session->csrfToken()) ?>">
                        <button type="submit">Odhlásit se</button>
                    </form>
                <?php else: ?>
                    <a href="/login">Přihlásit se</a>
                    <a href="/register">Registrace</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main>
        <?= $content ?>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> BikeSwap – Maturitní práce, Jan Štefáček</p>
    </footer>

    <script src="/js/app.js"></script>
</body>
</html>