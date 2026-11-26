<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Di hosting, HTTPS biasanya ditangani proxy/load balancer di depan server.
        // Tanpa ini Laravel mengira request masih http:// dan membuat link/aset yang salah.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

// File Manager InfinityFree tidak bisa membuat file bernama ".env".
// Kalau .env tidak ada tapi env.txt ada, konfigurasi dibaca dari env.txt.
// (env.txt tetap tidak bisa dibuka dari browser karena .htaccess mengarahkan semua request ke public/.)
if (! file_exists($app->basePath('.env')) && file_exists($app->basePath('env.txt'))) {
    $app->loadEnvironmentFrom('env.txt');
}

return $app;
