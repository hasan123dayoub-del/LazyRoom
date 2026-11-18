<?php

/*
 * Vercel entry point: vercel.json sends every request that is not a file in public/ here,
 * and this hands it to Laravel as usual. On Vercel only /tmp is writable, so compiled
 * views and the framework caches live there (see the env block in vercel.json).
 */

@mkdir('/tmp/views', 0755, true);

// Vercel gives every deployment its public address; use it unless APP_URL is set.
if (! getenv('APP_URL') && getenv('VERCEL_PROJECT_PRODUCTION_URL')) {
    $url = 'https://'.getenv('VERCEL_PROJECT_PRODUCTION_URL');
    putenv("APP_URL={$url}");
    $_ENV['APP_URL'] = $_SERVER['APP_URL'] = $url;
}

// This file lives at /api/index.php, so Laravel would take /api as the app's base path and
// strip it from every URL (/api/projects would become /projects). The app is at the root.
$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/../public/index.php';

require __DIR__.'/../public/index.php';
