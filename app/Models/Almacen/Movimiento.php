<?php

namespace App\Models\Almacen;

use App\Models\Parametro\Detalle;
use App\Models\Seguridad\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Movimiento extends Model
{
	use SoftDeletes;

	protected $table = 'almacen.movimiento';

	protected $primaryKey = 'idMovimiento';

	public $incrementing = true;

	protected $keyType = 'int';

	/*
	* La tabla original no utiliza
	* created_at / updated_at.
	*/
	public $timestamps = false;

	protected $fillable = [
		'idInventario',
		'idTipo',
		'cantidad',
		'fechaRegistro',
		'idUsuarioRegistro',
		'fechaBaja',
		'IdUsuarioBaja',
		'activo',
	];

	protected $casts = [
		'idMovimiento' => 'integer',
		'idInventario' => 'integer',
		'idTipo' => 'integer',
		'cantidad' => 'decimal:5',
		'fechaRegistro' => 'datetime',
		'idUsuarioRegistro' => 'integer',
		'fechaBaja' => 'datetime',
		'IdUsuarioBaja' => 'integer',
		'activo' => 'boolean',
		'deleted_at' => 'datetime',
	];

	/*
	|--------------------------------------------------------------------------
	| Relaciones
	|--------------------------------------------------------------------------
	*/

	public function inventario(): BelongsTo
	{
		return $this->belongsTo(
			Inventario::class,
			'idInventario',
			'idInventario'
		);
	}

	public function tipo(): BelongsTo
	{
		return $this->belongsTo(
			Detalle::class,
			'idTipo',
			'idDetalle'
		);
	}

	public function usuarioRegistro(): BelongsTo
	{
		return $this->belongsTo(
			Usuario::class,
			'idUsuarioRegistro',
			'idUsuario'
		);
	}

	public function usuarioBaja(): BelongsTo
	{
		return $this->belongsTo(
			Usuario::class,
			'IdUsuarioBaja',
			'idUsuario'
		);
	}

	/*
	|--------------------------------------------------------------------------
	| Scopes
	|--------------------------------------------------------------------------
	*/

	public function scopeActivos(
		Builder $query
	): Builder {
		return $query->where(
			'activo',
			true
		);
	}

	public function scopeInactivos(
		Builder $query
	): Builder {
		return $query->where(
			'activo',
			false
		);
	}

	public function scopePorInventario(
		Builder $query,
		int $idInventario
	): Builder {
		return $query->where(
			'idInventario',
			$idInventario
		);
	}

	public function scopePorTipo(
		Builder $query,
		int $idTipo
	): Builder {
		return $query->where(
			'idTipo',
			$idTipo
		);
	}
}
