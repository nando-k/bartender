<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('empleado.persona', function (Blueprint $table) {
			$table->bigIncrements('idPersona');
			$table->string('numeroDocumento', 50);
			$table->string('complemento', 2)->nullable();
			$table->string('sexo', 255);
			$table->date('fechaNacimiento');
			$table->string('paterno', 80)->nullable();
			$table->string('materno', 80)->nullable();
			$table->string('nombres', 150);
			$table->timestamp('fechaRegistro');
			$table->timestamp('fechaBaja')->nullable();
			$table->boolean('activo')->default(true);
			$table->softDeletes();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('empleado.persona');
	}
};
