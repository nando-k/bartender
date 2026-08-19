<?php

use App\Http\Controllers\Empleado\CargoController;
use App\Http\Controllers\Empleado\ContactoController;
use App\Http\Controllers\Empleado\PersonaController;
use App\Http\Controllers\Empleado\TurnoController;
use Illuminate\Support\Facades\Route;

Route::prefix('empleado')->group(function () {
	Route::apiResource(
		'personas',
		PersonaController::class
	);

	Route::post(
		'personas/{idPersona}/restore',
		[PersonaController::class, 'restore']
	);

	Route::apiResource(
		'contactos',
		ContactoController::class
	);

	Route::post(
		'contactos/{idContacto}/restore',
		[ContactoController::class, 'restore']
	);

	Route::apiResource(
		'cargos',
		CargoController::class
	);

	Route::post(
		'cargos/{idCargo}/restore',
		[CargoController::class, 'restore']
	);

	/*
	|--------------------------------------------------------------------------
	| Turnos
	|--------------------------------------------------------------------------
	*/

	Route::apiResource(
		'turnos',
		TurnoController::class
	);

	Route::post(
		'turnos/{idTurno}/restore',
		[TurnoController::class, 'restore']
	);
});
