<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('almacen.categoria', function (Blueprint $table) {
			$table->bigIncrements('idCategoria');
			$table->string('descripcion', 300);
			$table->string('codigo', 50);
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
		Schema::dropIfExists('almacen.categoria');
	}
};
