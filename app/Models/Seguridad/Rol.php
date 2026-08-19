<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
	use SoftDeletes;

	protected $table = 'seguridad.rol';

	protected $primaryKey = 'idRol';

	public $incrementing = true;

	protected $keyType = 'int';

	public $timestamps = false;

	protected $fillable = [
		'nombre',
		'fechaRegistro',
		'fechaBaja',
		'activo',
	];

	protected $casts = [
		'idRol' => 'integer',
		'fechaRegistro' => 'datetime',
		'fechaBaja' => 'datetime',
		'activo' => 'boolean',
		'deleted_at' => 'datetime',
	];

	public function rolUsuarios(): HasMany
	{
		return $this->hasMany(
			RolUsuario::class,
			'idRol',
			'idRol'
		);
	}

	public function scopeActivos(Builder $query): Builder
	{
		return $query->where('activo', true);
	}

	public function scopeInactivos(Builder $query): Builder
	{
		return $query->where('activo', false);
	}
}
