<?php

namespace App\Models\Almacen;

use App\Models\Parametro\Detalle;
use App\Models\Seguridad\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venta extends Model
{
	use SoftDeletes;

	protected $table = 'almacen.venta';

	protected $primaryKey = 'idVenta';

	public $incrementing = true;

	protected $keyType = 'int';

	/*
	* La tabla original no tiene
	* created_at / updated_at.
	*/
	public $timestamps = false;

	protected $fillable = [
		'idInventario',
		'idCliente',
		'idTipo',
		'total',
		'idEstado',
		'fechaRegistro',
		'idUsuarioRegistro',
		'fechaBaja',
		'IdUsuarioBaja',
		'activo',
	];

	protected $casts = [
		'idVenta' => 'integer',
		'idInventario' => 'integer',
		'idCliente' => 'integer',
		'idTipo' => 'integer',
		'total' => 'decimal:5',
		'idEstado' => 'integer',
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

	public function cliente(): BelongsTo
	{
		return $this->belongsTo(
			Cliente::class,
			'idCliente',
			'idCliente'
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

	public function estado(): BelongsTo
	{
		return $this->belongsTo(
			Detalle::class,
			'idEstado',
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

	public function detalles(): HasMany
	{
		return $this->hasMany(
			DetalleVenta::class,
			'idVenta',
			'idVenta'
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

	public function scopePorCliente(
		Builder $query,
		int $idCliente
	): Builder {
		return $query->where(
			'idCliente',
			$idCliente
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

	public function scopePorEstado(
		Builder $query,
		int $idEstado
	): Builder {
		return $query->where(
			'idEstado',
			$idEstado
		);
	}
}
