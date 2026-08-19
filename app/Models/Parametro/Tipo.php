<?php

namespace App\Models\Parametro;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tipo extends Model
{
	use SoftDeletes;

	protected $table = 'parametro.tipo';

	protected $primaryKey = 'idTipo';

	public $incrementing = true;

	protected $keyType = 'int';

	public $timestamps = false;

	protected $fillable = [
		'nombre',
		'descripcion',
		'fechaRegistro',
		'fechaBaja',
		'activo',
	];

	protected $casts = [
		'idTipo' => 'integer',
		'fechaRegistro' => 'datetime',
		'fechaBaja' => 'datetime',
		'activo' => 'boolean',
		'deleted_at' => 'datetime',
	];

	public function detalles(): HasMany
	{
		return $this->hasMany(
			Detalle::class,
			'idTipo',
			'idTipo'
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
