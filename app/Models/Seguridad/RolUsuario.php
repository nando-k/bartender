<?php

namespace App\Models\Seguridad;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolUsuario extends Model
{
	use SoftDeletes;

	protected $table = 'seguridad.rolUsuario';

	protected $primaryKey = 'idRolUsuario';

	public $incrementing = true;

	protected $keyType = 'int';

	public $timestamps = false;

	protected $fillable = [
		'idUsuario',
		'idRol',
		'fechaRegistro',
		'fechaBaja',
		'activo',
	];

	protected $casts = [
		'idRolUsuario' => 'integer',
		'idUsuario' => 'integer',
		'idRol' => 'integer',
		'fechaRegistro' => 'datetime',
		'fechaBaja' => 'datetime',
		'activo' => 'boolean',
		'deleted_at' => 'datetime',
	];

	public function usuario(): BelongsTo
	{
		return $this->belongsTo(
			Usuario::class,
			'idUsuario',
			'idUsuario'
		);
	}

	public function rol(): BelongsTo
	{
		return $this->belongsTo(
			Rol::class,
			'idRol',
			'idRol'
		);
	}
}
