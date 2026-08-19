<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('almacen.movimiento', function (Blueprint $table) {
			$table->bigIncrements('idMovimiento');

			$table->unsignedBigInteger('idInventario');

			$table->unsignedBigInteger('idTipo');

			$table->decimal('cantidad', 15, 5);

			$table->timestamp('fechaRegistro');

			$table->unsignedBigInteger('idUsuarioRegistro');

			$table->timestamp('fechaBaja')->nullable();

			$table->unsignedBigInteger('IdUsuarioBaja')->nullable();

			$table->boolean('activo')->default(true);

			/*
			* SoftDeletes requerido para el proyecto.
			*/
			$table->softDeletes();

			/*
			* FK:
			* movimiento.idInventario
			* -> almacen.inventario.idInventario
			*/
			$table->foreign('idInventario')
				->references('idInventario')
				->on('almacen.inventario')
				->restrictOnDelete();

			/*
			* FK:
			* movimiento.idTipo
			* -> parametro.detalle.idDetalle
			*/
			$table->foreign('idTipo')
				->references('idDetalle')
				->on('parametro.detalle')
				->restrictOnDelete();

			/*
			* FK:
			* movimiento.idUsuarioRegistro
			* -> seguridad.usuario.idUsuario
			*/
			$table->foreign('idUsuarioRegistro')
				->references('idUsuario')
				->on('seguridad.usuario')
				->restrictOnDelete();

			/*
			* FK:
			* movimiento.IdUsuarioBaja
			* -> seguridad.usuario.idUsuario
			*/
			$table->foreign('IdUsuarioBaja')
				->references('idUsuario')
				->on('seguridad.usuario')
				->restrictOnDelete();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('almacen.movimiento');
	}
};
