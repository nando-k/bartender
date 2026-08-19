<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('empleado.contacto', function (Blueprint $table) {
			$table->bigIncrements('idContacto');
			$table->unsignedBigInteger('idPersona');
			$table->string('celular', 20)->nullable();
			$table->string('telefono', 20)->nullable();
			$table->string('celularReferencia', 20)->nullable();
			$table->string('correo', 150)->nullable();
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
		Schema::dropIfExists('empleado.contacto');
	}
};
