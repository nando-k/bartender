<?php

use App\Http\Controllers\Seguridad\RolController;
use App\Http\Controllers\Seguridad\RolUsuarioController;
use App\Http\Controllers\Seguridad\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::prefix('seguridad')->group(function () {

	/*
	|--------------------------------------------------------------------------
	| Usuarios
	|--------------------------------------------------------------------------
	*/

	Route::apiResource(
		'usuarios',
		UsuarioController::class
	);

	Route::post(
		'usuarios/{idUsuario}/restore',
		[UsuarioController::class, 'restore']
	);

	/*
	|--------------------------------------------------------------------------
	| Roles
	|--------------------------------------------------------------------------
	*/

	Route::apiResource(
		'roles',
		RolController::class
	);

	Route::post(
		'roles/{idRol}/restore',
		[RolController::class, 'restore']
	);

	/*
	|--------------------------------------------------------------------------
	| Roles por usuario
	|--------------------------------------------------------------------------
	*/

	Route::apiResource(
		'rolUsuarios',
		RolUsuarioController::class
	);

	Route::post(
		'rolUsuarios/{idRolUsuario}/restore',
		[RolUsuarioController::class, 'restore']
	);
});

/*
Esto genera:
GET      /api/seguridad/usuarios
POST     /api/seguridad/usuarios
GET      /api/seguridad/usuarios/{idUsuario}
PUT      /api/seguridad/usuarios/{idUsuario}
PATCH    /api/seguridad/usuarios/{idUsuario}
DELETE   /api/seguridad/usuarios/{idUsuario}
POST     /api/seguridad/usuarios/{idUsuario}/restore

GET     /api/seguridad/roles
POST    /api/seguridad/roles
GET     /api/seguridad/roles/{idRol}
PUT     /api/seguridad/roles/{idRol}
PATCH   /api/seguridad/roles/{idRol}
DELETE  /api/seguridad/roles/{idRol}
POST    /api/seguridad/roles/{idRol}/restore
*/
