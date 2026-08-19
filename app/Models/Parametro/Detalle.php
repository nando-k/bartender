<?php

namespace App\Models\Parametro;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Detalle extends Model
{
	use SoftDeletes;

	protected $table = 'parametro.detalle';

	protected $primaryKey = 'idDetalle';

	public $incrementing = true;

	protected $keyType = 'int';

	public $timestamps = false;

	protected $fillable = [
		'idTipo',
		'nombre',
		'descripcion',
		'fechaRegistro',
		'fechaBaja',
		'activo',
	];

	protected $casts = [
		'idDetalle' => 'integer',
		'idTipo' => 'integer',
		'fechaRegistro' => 'datetime',
		'fechaBaja' => 'datetime',
		'activo' => 'boolean',
		'deleted_at' => 'datetime',
	];

	public function tipo(): BelongsTo
	{
		return $this->belongsTo(
			Tipo::class,
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
