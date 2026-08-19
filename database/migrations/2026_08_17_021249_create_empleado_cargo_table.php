<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('empleado.cargo', function (Blueprint $table) {
			$table->bigIncrements('idCargo');
			$table->string('nombre', 150);
			$table->text('descripcion')->nullable();
			$table->timestamp('fechaRegistro');
			$table->timestamp('fechaBaja')->nullable();
			$table->boolean('activo')->default(true);
			$table->softDeletes();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('empleado.cargo');
	}
};
