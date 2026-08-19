<?php

namespace App\Models\Almacen;

use App\Models\Seguridad\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventario extends Model
{
	use SoftDeletes;

	protected $table = 'almacen.inventario';

	protected $primaryKey = 'idInventario';

	public $incrementing = true;

	protected $keyType = 'int';

	/*
	* La tabla original no tiene created_at ni updated_at.
	*/
	public $timestamps = false;

	protected $fillable = [
		'idProducto',
		'precioUnitario',
		'cantidad',
		'precioTotal',
		'fechaRegistro',
		'idUsuarioRegistro',
	];

	protected $casts = [
		'idInventario' => 'integer',
		'idProducto' => 'integer',
		'precioUnitario' => 'decimal:5',
		'cantidad' => 'decimal:5',
		'precioTotal' => 'decimal:5',
		'fechaRegistro' => 'datetime',
		'idUsuarioRegistro' => 'integer',
		'deleted_at' => 'datetime',
	];

	/*
	|--------------------------------------------------------------------------
	| Relaciones
	|--------------------------------------------------------------------------
	*/

	public function producto(): BelongsTo
	{
		return $this->belongsTo(
			Producto::class,
			'idProducto',
			'idProducto'
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

	/*
	|--------------------------------------------------------------------------
	| Scopes
	|--------------------------------------------------------------------------
	*/

	public function scopePorProducto(
		Builder $query,
		int $idProducto
	): Builder {
		return $query->where(
			'idProducto',
			$idProducto
		);
	}
}
