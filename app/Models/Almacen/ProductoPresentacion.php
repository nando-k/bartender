<?php
namespace App\Models\Almacen;
use App\Models\Seguridad\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class ProductoPresentacion extends Model
{
	use SoftDeletes;
	protected $table = 'almacen.productoPresentacion';
	protected $primaryKey = 'idProductoPresentacion';
	public $incrementing = true;
	protected $keyType = 'int';
	public $timestamps = false;
	protected $fillable = [
		'idProducto',
		'nombre',
		'codigo',
		'cantidadBase',
		'precio',
		'fechaRegistro',
		'idUsuarioRegistro',
		'fechaBaja',
		'IdUsuarioBaja',
		'activo',
	];
	protected $casts = [
		'idProductoPresentacion' => 'integer',
		'idProducto' => 'integer',
		'cantidadBase' => 'decimal:5',
		'precio' => 'decimal:5',
		'fechaRegistro' => 'datetime',
		'idUsuarioRegistro' => 'integer',
		'fechaBaja' => 'datetime',
		'IdUsuarioBaja' => 'integer',
		'activo' => 'boolean',
		'deleted_at' => 'datetime',
	];
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
