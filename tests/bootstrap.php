<?php

require __DIR__ . '/../vendor/autoload.php';

$dir = __DIR__;
$envPath = null;
// Traverse upwards through the directory structure
while ($dir !== dirname($dir)) {
    $envPath = $dir . '/.env.testing';

    if (file_exists($envPath)) {
        return $envPath;
    }

    // Move up one level
    $dir = dirname($dir);
}

// Load .env.testing if it exists
$dotenv = Dotenv\Dotenv::createImmutable($envPath);
$dotenv->safeLoad();
