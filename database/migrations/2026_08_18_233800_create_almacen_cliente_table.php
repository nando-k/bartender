<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('almacen.cliente', function (Blueprint $table) {
			$table->bigIncrements('idCliente');

			$table->string('razonSocial', 300);

			$table->string('numeroDocumento', 50);

			$table->timestamp('fechaRegistro');

			$table->unsignedBigInteger('idUsuarioRegistro');

			$table->timestamp('fechaBaja')->nullable();

			$table->unsignedBigInteger('IdUsuarioBaja')->nullable();

			$table->boolean('activo')->default(true);

			/*
			* SoftDeletes requerido por el proyecto.
			*/
			$table->softDeletes();

			/*
			* FK:
			* cliente.idUsuarioRegistro
			* -> seguridad.usuario.idUsuario
			*/
			$table->foreign('idUsuarioRegistro')
				->references('idUsuario')
				->on('seguridad.usuario')
				->restrictOnDelete();

			/*
			* FK:
			* cliente.IdUsuarioBaja
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
		Schema::dropIfExists('almacen.cliente');
	}
};
