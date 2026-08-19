<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('almacen.inventario', function (Blueprint $table) {
			$table->bigIncrements('idInventario');
			$table->unsignedBigInteger('idProducto');
			$table->decimal('precioUnitario', 15, 5);
			$table->decimal('cantidad', 15, 5);
			$table->decimal('precioTotal', 15, 5);
			$table->timestamp('fechaRegistro');
			$table->unsignedBigInteger('idUsuarioRegistro');

			/*
			* Soft Delete requerido para el proyecto.
			*
			* Esta columna no existe en el SQL original,
			* pero se agrega para utilizar SoftDeletes de Laravel.
			*/
			$table->softDeletes();

			/*
			* FK hacia almacen.producto
			*/
			$table->foreign('idProducto')
				->references('idProducto')
				->on('almacen.producto')
				->restrictOnDelete();

			/*
			* FK hacia seguridad.usuario
			*/
			$table->foreign('idUsuarioRegistro')
				->references('idUsuario')
				->on('seguridad.usuario')
				->restrictOnDelete();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('almacen.inventario');
	}
};
