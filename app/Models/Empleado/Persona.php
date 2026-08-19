<?php

namespace App\Models\Empleado;

use App\Models\Seguridad\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Persona extends Model
{
	use SoftDeletes;

	protected $table = 'empleado.persona';

	protected $primaryKey = 'idPersona';

	public $incrementing = true;

	protected $keyType = 'int';

	public $timestamps = false;

	protected $fillable = [
		'numeroDocumento',
		'complemento',
		'sexo',
		'fechaNacimiento',
		'paterno',
		'materno',
		'nombres',
		'fechaRegistro',
		'fechaBaja',
		'activo',
	];

	protected $casts = [
		'idPersona' => 'integer',
		'fechaNacimiento' => 'date',
		'fechaRegistro' => 'datetime',
		'fechaBaja' => 'datetime',
		'activo' => 'boolean',
		'deleted_at' => 'datetime',
	];

	public function usuario(): HasOne
	{
		return $this->hasOne(
			Usuario::class,
			'idPersona',
			'idPersona'
		);
	}

	public function contactos(): HasMany
	{
		return $this->hasMany(
			Contacto::class,
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

	public function getNombreCompletoAttribute(): string
	{
		return trim(
			implode(' ', array_filter([
				$this->nombres,
				$this->paterno,
				$this->materno,
			]))
		);
	}

	public function getDocumentoCompletoAttribute(): string
	{
		if (empty($this->complemento)) {
			return $this->numeroDocumento;
		}

		return $this->numeroDocumento . '-' . $this->complemento;
	}
}
