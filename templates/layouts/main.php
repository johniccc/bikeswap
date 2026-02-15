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
            <button class="nav-toggle" id="nav-toggle" aria-label="Menu">☰</button>

            <div class="nav-links" id="nav-links">
                <a href="/stolen">Odcizená kola</a>

                <?php if (isset($session) && $session->isLoggedIn()): ?>
                    <a href="/shared">Sdílená kola</a>
                    <a href="/dashboard">Moje kola</a>
                    <a href="/reservations">Moje rezervace</a>
                    <a href="/bike/new">Registrovat kolo</a>
                    <a href="/notifications" class="nav-notifications" id="nav-notifications">
                        Oznámení
                        <span class="notification-badge" id="notification-badge"></span>
                    </a>
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

    <?php if (isset($session) && $session->isLoggedIn()): ?>
    <script>
    (function() {
        function updateBadge() {
            fetch('/notifications/count', {
                headers: { 'Accept': 'application/json' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var badge = document.getElementById('notification-badge');
                if (!badge) return;

                if (data.unread_count > 0) {
                    badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                    badge.classList.add('visible');
                } else {
                    badge.classList.remove('visible');
                }
            })
            .catch(function() {});
        }

        updateBadge();
        setInterval(updateBadge, 60000);
    })();
    </script>
    <?php endif; ?>
</body>
</html>