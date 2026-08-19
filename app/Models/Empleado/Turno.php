<?php

namespace App\Models\Empleado;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Turno extends Model
{
	use SoftDeletes;

	protected $table = 'empleado.turno';

	protected $primaryKey = 'idTurno';

	public $incrementing = true;

	protected $keyType = 'int';

	public $timestamps = false;

	protected $fillable = [
		'idPersona',
		'dia',
		'horaIngreso',
		'horaSalida',
		'fechaRegistro',
		'fechaBaja',
		'activo',
	];

	protected $casts = [
		'idTurno' => 'integer',
		'idPersona' => 'integer',
		'horaIngreso' => 'datetime',
		'horaSalida' => 'datetime',
		'fechaRegistro' => 'datetime',
		'fechaBaja' => 'datetime',
		'activo' => 'boolean',
		'deleted_at' => 'datetime',
	];

	public function persona(): BelongsTo
	{
		return $this->belongsTo(
			Persona::class,
			'idPersona',
			'idPersona'
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
