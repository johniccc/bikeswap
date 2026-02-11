<?php

declare(strict_types=1);

/**
 * BikeSwap - Front Controller
 * 
 * Every request enters the application through this file.
 * Apache's .htaccess rewrites all URLs here.
 */

// Autoloading (Composer)
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Load application config
$config = require __DIR__ . '/../config/app.php';

// Bootstrap and run
$app = new App\Core\App($config);
$app->run();