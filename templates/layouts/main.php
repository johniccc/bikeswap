<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'BikeSwap') ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header>
        <nav>
            <a href="/">BikeSwap</a>
            <!-- TODO: Navigation links -->
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