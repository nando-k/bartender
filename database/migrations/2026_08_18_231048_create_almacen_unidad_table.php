<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('almacen.unidadMedida', function (Blueprint $table) {
			$table->bigIncrements('idUnidadMedida');
			$table->string('codigo', 50);
			$table->string('descripcion', 300);
			$table->decimal('factorBase', 15, 5);
			$table->timestamp('fechaRegistro');
			$table->unsignedBigInteger('idUsuarioRegistro');
			$table->timestamp('fechaBaja')->nullable();
			$table->unsignedBigInteger('IdUsuarioBaja')->nullable();
			$table->boolean('activo')->default(true);
			$table->softDeletes();
			$table->foreign('idUsuarioRegistro')
				->references('idUsuario')
				->on('seguridad.usuario')
				->restrictOnDelete();
			$table->foreign('IdUsuarioBaja')
				->references('idUsuario')
				->on('seguridad.usuario')
				->restrictOnDelete();
		});
	}
	public function down(): void
	{
		Schema::dropIfExists('almacen.unidadMedida');
	}
};
