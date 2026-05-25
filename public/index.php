<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Ensure upload temp + KYC folders exist under public/
$publicTmp = __DIR__.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'uploads';
$publicKyc = __DIR__.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'kyc';
foreach ([$publicTmp, $publicKyc] as $dir) {
    if (! is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
