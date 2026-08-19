<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		$schemas = [
			'seguridad',
			'empleado',
			'parametro',
			'almacen',
		];

		foreach ($schemas as $schema) {
			DB::statement("CREATE SCHEMA IF NOT EXISTS {$schema}");
		}
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		$schemas = [
			'seguridad',
			'empleado',
			'parametro',
			'almacen',
		];

		foreach ($schemas as $schema) {
			DB::statement("DROP SCHEMA IF EXISTS {$schema} CASCADE");
		}
	}
};
