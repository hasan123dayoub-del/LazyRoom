<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Live demo only: Vercel Cron calls this every night (vercel.json) with the CRON_SECRET,
// so the demo database goes back to its sample data.
Route::get('/demo/reset', function (Request $request) {
    $secret = env('CRON_SECRET');
    abort_unless($secret && hash_equals("Bearer {$secret}", (string) $request->header('Authorization')), 401);

    Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);

    return response()->json(['reset' => true]);
});
