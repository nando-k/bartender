<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('empleado.turno', function (Blueprint $table) {
			$table->bigIncrements('idTurno');
			$table->unsignedBigInteger('idPersona');
			$table->string('dia', 50);
			$table->timestamp('horaIngreso');
			$table->timestamp('horaSalida');
			$table->timestamp('fechaRegistro');
			$table->timestamp('fechaBaja')->nullable();
			$table->boolean('activo')->default(true);
			$table->softDeletes();

			$table->foreign('idPersona')
				->references('idPersona')
				->on('empleado.persona')
				->restrictOnDelete();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('empleado.turno');
	}
};
