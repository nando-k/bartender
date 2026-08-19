<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('seguridad.rolUsuario', function (Blueprint $table) {
			$table->bigIncrements('idRolUsuario');
			$table->unsignedBigInteger('idUsuario');
			$table->unsignedBigInteger('idRol');
			$table->timestamp('fechaRegistro');
			$table->timestamp('fechaBaja')->nullable();
			$table->boolean('activo')->default(true);
			$table->softDeletes();

			$table->foreign('idUsuario')
				->references('idUsuario')
				->on('seguridad.usuario')
				->restrictOnDelete();

			$table->foreign('idRol')
				->references('idRol')
				->on('seguridad.rol')
				->restrictOnDelete();

			$table->unique('idUsuario');
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('seguridad.rolUsuario');
	}
};
