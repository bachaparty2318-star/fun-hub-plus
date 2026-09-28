<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn ($request, $exception) => $request->is('admin/api', 'admin/api/*', 'user/api', 'user/api/*', 'visitor/api', 'visitor/api/*') || $request->expectsJson());
        $exceptions->render(function (QueryException $exception, $request) {
            if ($request->is('admin/api/*', 'user/api/*', 'visitor/api/*') && in_array($exception->errorInfo[1] ?? null, [1062, 1451, 1452], true)) {
                return response()->json(['message' => 'This change conflicts with an existing or related record.'], 409);
            }
        });
    })->create();
