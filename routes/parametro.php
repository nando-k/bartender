<?php

use App\Http\Controllers\Parametro\DetalleController;
use App\Http\Controllers\Parametro\TipoController;
use Illuminate\Support\Facades\Route;

Route::prefix('parametro')->group(function () {

	/*
	|--------------------------------------------------------------------------
	| Tipos
	|--------------------------------------------------------------------------
	*/

	Route::apiResource(
		'tipos',
		TipoController::class
	);

	Route::post(
		'tipos/{idTipo}/restore',
		[TipoController::class, 'restore']
	);

	/*
    |--------------------------------------------------------------------------
    | Tipos
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'tipos',
        TipoController::class
    );

    Route::post(
        'tipos/{idTipo}/restore',
        [TipoController::class, 'restore']
    );

    /*
    |--------------------------------------------------------------------------
    | Detalles
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'detalles',
        DetalleController::class
    );

    Route::post(
        'detalles/{idDetalle}/restore',
        [DetalleController::class, 'restore']
    );
});
