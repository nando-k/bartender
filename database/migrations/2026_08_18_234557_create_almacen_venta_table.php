<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('almacen.venta', function (Blueprint $table) {
			$table->bigIncrements('idVenta');
			$table->unsignedBigInteger('idInventario');
			$table->unsignedBigInteger('idCliente');
			$table->unsignedBigInteger('idTipo');
			$table->decimal('total', 15, 5);
			$table->unsignedBigInteger('idEstado');
			$table->timestamp('fechaRegistro');
			$table->unsignedBigInteger('idUsuarioRegistro');
			$table->timestamp('fechaBaja')->nullable();
			$table->unsignedBigInteger('IdUsuarioBaja')->nullable();
			$table->boolean('activo')->default(true);

			/*
			* SoftDelete de Laravel.
			*/
			$table->softDeletes();

			/*
			* FK:
			* venta.idInventario
			* -> almacen.inventario.idInventario
			*/
			$table->foreign('idInventario')
				->references('idInventario')
				->on('almacen.inventario')
				->restrictOnDelete();

			/*
			* FK:
			* venta.idCliente
			* -> almacen.cliente.idCliente
			*/
			$table->foreign('idCliente')
				->references('idCliente')
				->on('almacen.cliente')
				->restrictOnDelete();

			/*
			* FK:
			* venta.idTipo
			* -> parametro.detalle.idDetalle
			*
			* Se conserva porque así está definido
			* en el SQL original.
			*/
			$table->foreign('idTipo')
				->references('idDetalle')
				->on('parametro.detalle')
				->restrictOnDelete();

			/*
			* FK:
			* venta.idEstado
			* -> parametro.detalle.idDetalle
			*/
			$table->foreign('idEstado')
				->references('idDetalle')
				->on('parametro.detalle')
				->restrictOnDelete();

			/*
			* FK:
			* venta.idUsuarioRegistro
			* -> seguridad.usuario.idUsuario
			*/
			$table->foreign('idUsuarioRegistro')
				->references('idUsuario')
				->on('seguridad.usuario')
				->restrictOnDelete();

			/*
			* FK:
			* venta.IdUsuarioBaja
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
		Schema::dropIfExists('almacen.venta');
	}
};
