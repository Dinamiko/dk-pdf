<?php
declare(strict_types=1);

$autoloader = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    throw new RuntimeException(
        'Composer autoloader not found. Please run "composer install" before running tests.'
    );
}

require_once $autoloader;

$baseDir = dirname(__FILE__);
$pluginDir = dirname($baseDir, 2);

$wpDir = dirname($pluginDir, 3);
require_once $wpDir . '/wp-load.php';

