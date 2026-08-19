<?php

namespace App\Models\Seguridad;

use App\Models\Empleado\Persona;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Usuario extends Model
{
	use SoftDeletes;

	protected $table = 'seguridad.usuario';

	protected $primaryKey = 'idUsuario';

	public $incrementing = true;

	protected $keyType = 'int';

	public $timestamps = false;

	protected $fillable = [
		'idPersona',
		'cuenta',
		'passwordHash',
		'fechaRegistro',
		'fechaBaja',
		'activo',
	];

	protected $hidden = [
		'passwordHash',
	];

	protected $casts = [
		'idUsuario' => 'integer',
		'idPersona' => 'integer',
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

	public function rolUsuarios(): HasMany
	{
		return $this->hasMany(
			RolUsuario::class,
			'idUsuario',
			'idUsuario'
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
