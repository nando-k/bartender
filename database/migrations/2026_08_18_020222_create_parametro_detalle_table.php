<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('parametro.detalle', function (Blueprint $table) {
			$table->bigIncrements('idDetalle');
			$table->unsignedBigInteger('idTipo');
			$table->string('nombre', 100);
			$table->text('descripcion')->nullable();
			$table->timestamp('fechaRegistro');
			$table->timestamp('fechaBaja')->nullable();
			$table->boolean('activo')->default(true);
			$table->softDeletes();

			$table->foreign('idTipo')
				->references('idTipo')
				->on('parametro.tipo')
				->restrictOnDelete();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('parametro.detalle');
	}
};
