<?php
namespace App\Models\Almacen;
use App\Models\Seguridad\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class Producto extends Model
{
	use SoftDeletes;
	protected $table = 'almacen.producto';
	protected $primaryKey = 'idProducto';
	public $incrementing = true;
	protected $keyType = 'int';
	public $timestamps = false;
	protected $fillable = [
		'idUnidadMedida',
		'descripcion',
		'codigo',
		'fechaRegistro',
		'idUsuarioRegistro',
		'fechaBaja',
		'IdUsuarioBaja',
		'activo',
	];
	protected $casts = [
		'idProducto' => 'integer',
		'idUnidadMedida' => 'integer',
		'fechaRegistro' => 'datetime',
		'idUsuarioRegistro' => 'integer',
		'fechaBaja' => 'datetime',
		'IdUsuarioBaja' => 'integer',
		'activo' => 'boolean',
		'deleted_at' => 'datetime',
	];
	public function unidadMedida(): BelongsTo
	{
		return $this->belongsTo(
			UnidadMedida::class,
			'idUnidadMedida',
			'idUnidadMedida'
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
	public function scopeActivos(Builder $query): Builder
	{
		return $query->where('activo', true);
	}
	public function scopeInactivos(Builder $query): Builder
	{
		return $query->where('activo', false);
	}
}
