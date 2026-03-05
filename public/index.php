<?php

declare(strict_types=1);

/**
 * BikeSwap - Front Controller
 * 
 * Every request enters the application through this file.
 * Apache's .htaccess rewrites all URLs here.
 */

// Base path — supports both local dev (public/../) and server (everything in web/)
$basePath = is_dir(__DIR__ . '/vendor') ? __DIR__ : __DIR__ . '/..';

// Autoloading (Composer)
require_once $basePath . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable($basePath);
$dotenv->load();

// Load application config
$config = require $basePath . '/config/app.php';

// Bootstrap and run
$app = new App\Core\App($config);
$app->run();