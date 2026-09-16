<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (QueryException $exception, Request $request) {
            if ($request->expectsJson() || $request->isMethod('GET')) {
                return null;
            }

            $errorCode = $exception->errorInfo[1] ?? null;
            $message = match ($errorCode) {
                1062 => 'Data tidak dapat disimpan karena sudah ada data dengan informasi yang sama.',
                1451, 1452 => 'Data tidak dapat diproses karena masih terhubung dengan data lain.',
                default => 'Data tidak dapat diproses. Silakan periksa kembali input dan coba lagi.',
            };

            return back()->withInput()->withErrors(['form' => $message]);
        });
    })->create();
