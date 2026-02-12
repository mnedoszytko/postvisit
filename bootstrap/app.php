<?php

use App\Http\Middleware\AuditMiddleware;
use App\Http\Middleware\EnsureDocumentAccess;
use App\Http\Middleware\EnsureNotificationAccess;
use App\Http\Middleware\EnsurePatientAccess;
use App\Http\Middleware\EnsureVisitAccess;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'audit' => AuditMiddleware::class,
            'patient.access' => EnsurePatientAccess::class,
            'visit.access' => EnsureVisitAccess::class,
            'document.access' => EnsureDocumentAccess::class,
            'notification.access' => EnsureNotificationAccess::class,
        ]);

        $middleware->trustProxies(at: '*');
        $middleware->statefulApi();

        $middleware->api(prepend: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
