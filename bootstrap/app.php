<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
		then: function () {
			// módulo de seguridad
			Route::middleware('api')
				->prefix('api')
				->group(base_path('routes/seguridad.php'));

			// módulo de empleado
			Route::middleware('api')
				->prefix('api')
				->group(base_path('routes/empleado.php'));

			// módulo de almacén
			Route::middleware('api')
				->prefix('api')
				->group(base_path('routes/almacen.php'));

			// módulo de parametro
			Route::middleware('api')
				->prefix('api')
				->group(base_path('routes/parametro.php'));
		},

	)
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
