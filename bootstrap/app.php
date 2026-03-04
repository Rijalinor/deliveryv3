<?php

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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->trustProxies(at: '*');

        // API rate limiting: 60 requests per minute per IP
        $middleware->throttleApi('60,1');

        // Exclude API routes from CSRF verification for token-based auth
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error_code' => 'UNAUTHENTICATED',
                    'message' => 'Unauthenticated or token expired.',
                    'request_id' => (string) \Illuminate\Support\Str::uuid(),
                ], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error_code' => 'VALIDATION_FAILED',
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                    'request_id' => (string) \Illuminate\Support\Str::uuid(),
                ], 422);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error_code' => 'NOT_FOUND',
                    'message' => 'Resource not found or does not exist.',
                    'request_id' => (string) \Illuminate\Support\Str::uuid(),
                ], 404);
            }
        });

        $exceptions->render(function (\Exception $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                // Return generic message in production unless it's an HttpException, to not expose stack traces.
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                $message = config('app.debug') ? $e->getMessage() : 'Internal Server Error';

                return response()->json([
                    'success' => false,
                    'error_code' => 'SERVER_ERROR',
                    'message' => $message,
                    'request_id' => (string) \Illuminate\Support\Str::uuid(),
                ], $statusCode);
            }
        });
    })->create();
