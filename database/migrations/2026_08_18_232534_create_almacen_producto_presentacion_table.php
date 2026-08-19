<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
	public function up(): void
	{
		Schema::create('almacen.productoPresentacion', function (Blueprint $table) {
			$table->bigIncrements('idProductoPresentacion');
			$table->unsignedBigInteger('idProducto');
			$table->string('nombre', 300);
			$table->string('codigo', 50);
			$table->decimal('cantidadBase', 15, 5);
			$table->decimal('precio', 15, 5);
			$table->timestamp('fechaRegistro');
			$table->unsignedBigInteger('idUsuarioRegistro');
			$table->timestamp('fechaBaja')->nullable();
			$table->unsignedBigInteger('IdUsuarioBaja')->nullable();
			$table->boolean('activo')->default(true);
			$table->softDeletes();
			$table->foreign('idProducto')
				->references('idProducto')
				->on('almacen.producto')
				->restrictOnDelete();
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
		Schema::dropIfExists('almacen.productoPresentacion');
	}
};
