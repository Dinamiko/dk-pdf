<?php
declare(strict_types=1);

$autoloader = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    throw new RuntimeException(
        'Composer autoloader not found. Please run "composer install" before running tests.'
    );
}

require_once $autoloader;
