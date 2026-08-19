<?php

use App\Http\Controllers\Almacen\CategoriaController;
use App\Http\Controllers\Almacen\ClienteController;
use App\Http\Controllers\Almacen\InventarioController;
use App\Http\Controllers\Almacen\MovimientoController;
use App\Http\Controllers\Almacen\ProductoController;
use App\Http\Controllers\Almacen\ProductoPresentacionController;
use App\Http\Controllers\Almacen\ProveedorController;
use App\Http\Controllers\Almacen\UnidadMedidaController;
use App\Http\Controllers\Almacen\VentaController;
use Illuminate\Support\Facades\Route;

Route::prefix('almacen')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Proveedores
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'proveedores',
        ProveedorController::class
    );

    Route::post(
        'proveedores/{idProveedor}/restore',
        [ProveedorController::class, 'restore']
    );


    /*
    |--------------------------------------------------------------------------
    | Unidades de medida
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'unidades-medida',
        UnidadMedidaController::class
    );

    Route::post(
        'unidades-medida/{idUnidadMedida}/restore',
        [UnidadMedidaController::class, 'restore']
    );


    /*
    |--------------------------------------------------------------------------
    | Categorías
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'categorias',
        CategoriaController::class
    );

    Route::post(
        'categorias/{idCategoria}/restore',
        [CategoriaController::class, 'restore']
    );


    /*
    |--------------------------------------------------------------------------
    | Productos
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'productos',
        ProductoController::class
    );

    Route::post(
        'productos/{idProducto}/restore',
        [ProductoController::class, 'restore']
    );


    /*
    |--------------------------------------------------------------------------
    | Presentaciones de productos
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'producto-presentaciones',
        ProductoPresentacionController::class
    );

    Route::post(
        'producto-presentaciones/{idProductoPresentacion}/restore',
        [ProductoPresentacionController::class, 'restore']
    );


    /*
    |--------------------------------------------------------------------------
    | Inventarios
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'inventarios',
        InventarioController::class
    );

    Route::post(
        'inventarios/{idInventario}/restore',
        [InventarioController::class, 'restore']
    );

	/*
    |--------------------------------------------------------------------------
    | Clientes
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'clientes',
        ClienteController::class
    );

    Route::post(
        'clientes/{idCliente}/restore',
        [ClienteController::class, 'restore']
    );

	/*
	|--------------------------------------------------------------------------
	| Movimientos
	|--------------------------------------------------------------------------
	*/

	Route::apiResource(
		'movimientos',
		MovimientoController::class
	);

	Route::post(
		'movimientos/{idMovimiento}/restore',
		[MovimientoController::class, 'restore']
	);

	/*
	|--------------------------------------------------------------------------
	| Ventas
	|--------------------------------------------------------------------------
	*/

	Route::apiResource(
		'ventas',
		VentaController::class
	);

	Route::post(
		'ventas/{idVenta}/restore',
		[VentaController::class, 'restore']
	);
});
