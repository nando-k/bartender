<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create('seguridad.usuario', function (Blueprint $table) {
			$table->bigIncrements('idUsuario');
			$table->unsignedBigInteger('idPersona');
			$table->string('cuenta', 100)->unique();
			$table->string('passwordHash', 300);
			$table->timestamp('fechaRegistro');
			$table->timestamp('fechaBaja')->nullable();
			$table->boolean('activo')->default(true);
			$table->softDeletes('deleted_at');

			$table->foreign('idPersona')
				->references('idPersona')
				->on('empleado.persona')
				->restrictOnDelete();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('seguridad.usuario');
	}
};
