<?php

namespace App\Models\Almacen;

use App\Models\Seguridad\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
	use SoftDeletes;

	protected $table = 'almacen.cliente';

	protected $primaryKey = 'idCliente';

	public $incrementing = true;

	protected $keyType = 'int';

	/*
	* La tabla original no utiliza
	* created_at / updated_at.
	*/
	public $timestamps = false;

	protected $fillable = [
		'razonSocial',
		'numeroDocumento',
		'fechaRegistro',
		'idUsuarioRegistro',
		'fechaBaja',
		'IdUsuarioBaja',
		'activo',
	];

	protected $casts = [
		'idCliente' => 'integer',
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
}
