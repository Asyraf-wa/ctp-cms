<?php

use App\Http\Middleware\NormalizeUrl;
use App\Models\NotFoundLog;
use App\Models\Redirect;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // SEO-10: one URL per page (lowercase, no trailing slash).
        $middleware->prepend(NormalizeUrl::class); // global: it must run before route matching

        // View beacon and kudos (ART-10, ART-12) are posted from response-cached pages.
        $middleware->validateCsrfTokens(except: ['api/articles/*/view', 'api/articles/*/kudos']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Redirects are only looked up when nothing else matched, so real pages always win (SEO-11, SLUG-03).
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->isMethod('GET') && ($redirect = Redirect::findForPath($request->getPathInfo()))) {
                return $redirect->toResponse();
            }

            // SEO-11: remember what was missing. Logging must never turn a 404 into a 500.
            try {
                NotFoundLog::record($request);
            } catch (Throwable) {
            }
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
