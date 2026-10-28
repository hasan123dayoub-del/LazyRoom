<?php
// Boots the app for the concurrency benchmark. Point DB_* at a scratch MySQL database: setup.php truncates tables.
$base = getenv('LAZYROOM_PATH') ?: dirname(__DIR__, 2);
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
