<?php
namespace App\Http\Controllers\Almacen;
use App\Http\Controllers\Controller;
use App\Http\Requests\Almacen\StoreProductoRequest;
use App\Http\Requests\Almacen\UpdateProductoRequest;
use App\Models\Almacen\Producto;
use App\Models\Almacen\UnidadMedida;
use App\Models\Seguridad\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ProductoController extends Controller
{
	public function index(Request $request): JsonResponse
	{
		$query = Producto::query()
			->with([
				'unidadMedida:idUnidadMedida,codigo,descripcion,factorBase,activo',
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			])
			->orderBy('idProducto', 'desc');
		if ($request->filled('buscar')) {
			$buscar = trim(
				$request->input('buscar')
			);
			$query->where(function ($q) use ($buscar) {
				$q->whereRaw(
					'LOWER("codigo") LIKE LOWER(?)',
					["%{$buscar}%"]
				)
				->orWhereRaw(
					'LOWER("descripcion") LIKE LOWER(?)',
					["%{$buscar}%"]
				);
			});
		}
		if ($request->filled('codigo')) {
			$codigo = trim(
				$request->input('codigo')
			);
			$query->whereRaw(
				'LOWER("codigo") = LOWER(?)',
				[$codigo]
			);
		}
		if ($request->filled('descripcion')) {
			$descripcion = trim(
				$request->input('descripcion')
			);
			$query->whereRaw(
				'LOWER("descripcion") = LOWER(?)',
				[$descripcion]
			);
		}
		if ($request->filled('idUnidadMedida')) {
			$query->where(
				'idUnidadMedida',
				$request->integer('idUnidadMedida')
			);
		}
		if ($request->has('activo')) {
			$query->where(
				'activo',
				$request->boolean('activo')
			);
		}
		if ($request->filled('idUsuarioRegistro')) {
			$query->where(
				'idUsuarioRegistro',
				$request->integer('idUsuarioRegistro')
			);
		}
		$productos = $query->paginate(
			$request->integer('perPage', 15)
		);
		return response()->json([
			'success' => true,
			'message' => 'Productos obtenidos correctamente.',
			'data' => $productos,
		]);
	}
	public function store(
		StoreProductoRequest $request
	): JsonResponse {
		return DB::transaction(function () use ($request) {
			$idUnidadMedida = $request->integer(
				'idUnidadMedida'
			);
			$descripcion = trim(
				$request->input('descripcion')
			);
			$codigo = trim(
				$request->input('codigo')
			);
			$idUsuarioRegistro = $request->integer(
				'idUsuarioRegistro'
			);
			/*
			* Verificamos la unidad de medida.
			*/
			$unidadMedida = UnidadMedida::find(
				$idUnidadMedida
			);
			if (!$unidadMedida) {
				return response()->json([
					'success' => false,
					'message' => 'La unidad de medida no existe.',
					'errors' => [
						'idUnidadMedida' => [
							'La unidad de medida indicada no está disponible.'
						],
					],
				], 422);
			}
			if (!$unidadMedida->activo) {
				return response()->json([
					'success' => false,
					'message' => 'La unidad de medida se encuentra inactiva.',
					'errors' => [
						'idUnidadMedida' => [
							'No se puede registrar un producto utilizando una unidad de medida inactiva.'
						],
					],
				], 422);
			}
			/*
			* Verificamos el usuario de registro.
			*/
			$usuarioRegistro = Usuario::find(
				$idUsuarioRegistro
			);
			if (!$usuarioRegistro) {
				return response()->json([
					'success' => false,
					'message' => 'El usuario de registro no existe o se encuentra eliminado.',
					'errors' => [
						'idUsuarioRegistro' => [
							'El usuario indicado no está disponible.'
						],
					],
				], 422);
			}
			if (!$usuarioRegistro->activo) {
				return response()->json([
					'success' => false,
					'message' => 'El usuario de registro se encuentra inactivo.',
					'errors' => [
						'idUsuarioRegistro' => [
							'No se puede registrar un producto con un usuario inactivo.'
						],
					],
				], 422);
			}
			/*
			* CONTROL DE DUPLICIDAD POR CÓDIGO.
			*
			* withTrashed() incluye registros eliminados
			* lógicamente.
			*/
			$existeCodigo = Producto::withTrashed()
				->whereRaw(
					'LOWER("codigo") = LOWER(?)',
					[$codigo]
				)
				->exists();
			if ($existeCodigo) {
				return response()->json([
					'success' => false,
					'message' => 'El código del producto ya se encuentra registrado.',
					'errors' => [
						'codigo' => [
							'Ya existe un producto con este código.'
						],
					],
				], 422);
			}
			/*
			* CONTROL DE DUPLICIDAD POR DESCRIPCIÓN.
			*/
			$existeDescripcion = Producto::withTrashed()
				->whereRaw(
					'LOWER("descripcion") = LOWER(?)',
					[$descripcion]
				)
				->exists();
			if ($existeDescripcion) {
				return response()->json([
					'success' => false,
					'message' => 'La descripción del producto ya se encuentra registrada.',
					'errors' => [
						'descripcion' => [
							'Ya existe un producto con esta descripción.'
						],
					],
				], 422);
			}
			$producto = new Producto();
			$producto->idUnidadMedida = $idUnidadMedida;
			$producto->descripcion = $descripcion;
			$producto->codigo = $codigo;
			$producto->fechaRegistro = $request->input(
				'fechaRegistro',
				now()
			);
			$producto->idUsuarioRegistro = $idUsuarioRegistro;
			$producto->fechaBaja = null;
			$producto->IdUsuarioBaja = null;
			$producto->activo = $request->has('activo')
				? $request->boolean('activo')
				: true;
			$producto->save();
			$producto->load([
				'unidadMedida:idUnidadMedida,codigo,descripcion,factorBase,activo',
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			]);
			return response()->json([
				'success' => true,
				'message' => 'Producto registrado correctamente.',
				'data' => $producto,
			], 201);
		});
	}
	public function show(
		int $idProducto
	): JsonResponse {
		$producto = Producto::with([
			'unidadMedida:idUnidadMedida,codigo,descripcion,factorBase,activo',
			'usuarioRegistro:idUsuario,cuenta,activo',
			'usuarioBaja:idUsuario,cuenta,activo',
		])->find($idProducto);
		if (!$producto) {
			return response()->json([
				'success' => false,
				'message' => 'Producto no encontrado.',
			], 404);
		}
		return response()->json([
			'success' => true,
			'message' => 'Producto obtenido correctamente.',
			'data' => $producto,
		]);
	}
	public function update(
		UpdateProductoRequest $request,
		int $idProducto
	): JsonResponse {
		return DB::transaction(function () use (
			$request,
			$idProducto
		) {
			$producto = Producto::find(
				$idProducto
			);
			if (!$producto) {
				return response()->json([
					'success' => false,
					'message' => 'Producto no encontrado.',
				], 404);
			}
			$idUnidadMedida = $request->integer(
				'idUnidadMedida'
			);
			$descripcion = trim(
				$request->input('descripcion')
			);
			$codigo = trim(
				$request->input('codigo')
			);
			$idUsuarioRegistro = $request->integer(
				'idUsuarioRegistro'
			);
			/*
			* Verificamos la unidad de medida.
			*/
			$unidadMedida = UnidadMedida::find(
				$idUnidadMedida
			);
			if (!$unidadMedida) {
				return response()->json([
					'success' => false,
					'message' => 'La unidad de medida no existe.',
					'errors' => [
						'idUnidadMedida' => [
							'La unidad de medida indicada no está disponible.'
						],
					],
				], 422);
			}
			if (!$unidadMedida->activo) {
				return response()->json([
					'success' => false,
					'message' => 'La unidad de medida se encuentra inactiva.',
					'errors' => [
						'idUnidadMedida' => [
							'No se puede actualizar el producto utilizando una unidad de medida inactiva.'
						],
					],
				], 422);
			}
			/*
			* Verificamos el usuario de registro.
			*/
			$usuarioRegistro = Usuario::find(
				$idUsuarioRegistro
			);
			if (!$usuarioRegistro) {
				return response()->json([
					'success' => false,
					'message' => 'El usuario de registro no existe o se encuentra eliminado.',
					'errors' => [
						'idUsuarioRegistro' => [
							'El usuario indicado no está disponible.'
						],
					],
				], 422);
			}
			if (!$usuarioRegistro->activo) {
				return response()->json([
					'success' => false,
					'message' => 'El usuario de registro se encuentra inactivo.',
					'errors' => [
						'idUsuarioRegistro' => [
							'No se puede actualizar el producto con un usuario inactivo.'
						],
					],
				], 422);
			}
			/*
			* CONTROL DE DUPLICIDAD POR CÓDIGO.
			*
			* Excluimos el producto actual.
			*/
			$existeCodigo = Producto::withTrashed()
				->whereRaw(
					'LOWER("codigo") = LOWER(?)',
					[$codigo]
				)
				->where(
					'idProducto',
					'<>',
					$producto->idProducto
				)
				->exists();
			if ($existeCodigo) {
				return response()->json([
					'success' => false,
					'message' => 'El código del producto ya se encuentra registrado.',
					'errors' => [
						'codigo' => [
							'Ya existe otro producto con este código.'
						],
					],
				], 422);
			}
			/*
			* CONTROL DE DUPLICIDAD POR DESCRIPCIÓN.
			*/
			$existeDescripcion = Producto::withTrashed()
				->whereRaw(
					'LOWER("descripcion") = LOWER(?)',
					[$descripcion]
				)
				->where(
					'idProducto',
					'<>',
					$producto->idProducto
				)
				->exists();
			if ($existeDescripcion) {
				return response()->json([
					'success' => false,
					'message' => 'La descripción del producto ya se encuentra registrada.',
					'errors' => [
						'descripcion' => [
							'Ya existe otro producto con esta descripción.'
						],
					],
				], 422);
			}
			$producto->idUnidadMedida = $idUnidadMedida;
			$producto->descripcion = $descripcion;
			$producto->codigo = $codigo;
			if ($request->filled('fechaRegistro')) {
				$producto->fechaRegistro = $request->input(
					'fechaRegistro'
				);
			}
			$producto->idUsuarioRegistro = $idUsuarioRegistro;
			if ($request->has('fechaBaja')) {
				$producto->fechaBaja = $request->input(
					'fechaBaja'
				);
			}
			if ($request->has('IdUsuarioBaja')) {
				$producto->IdUsuarioBaja = $request->input(
					'IdUsuarioBaja'
				);
			}
			if ($request->has('activo')) {
				$producto->activo = $request->boolean(
					'activo'
				);
			}
			$producto->save();
			$producto->load([
				'unidadMedida:idUnidadMedida,codigo,descripcion,factorBase,activo',
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			]);
			return response()->json([
				'success' => true,
				'message' => 'Producto actualizado correctamente.',
				'data' => $producto,
			]);
		});
	}
	public function destroy(
		int $idProducto
	): JsonResponse {
		return DB::transaction(function () use ($idProducto) {
			$producto = Producto::find(
				$idProducto
			);
			if (!$producto) {
				return response()->json([
					'success' => false,
					'message' => 'Producto no encontrado.',
				], 404);
			}
			/*
			* Baja lógica.
			*/
			$producto->activo = false;
			$producto->fechaBaja = now();
			$producto->save();
			$producto->delete();
			return response()->json([
				'success' => true,
				'message' => 'Producto eliminado correctamente.',
			]);
		});
	}
	public function restore(
		int $idProducto
	): JsonResponse {
		return DB::transaction(function () use ($idProducto) {
			$producto = Producto::withTrashed()
				->find($idProducto);
			if (!$producto) {
				return response()->json([
					'success' => false,
					'message' => 'Producto no encontrado.',
				], 404);
			}
			if (!$producto->trashed()) {
				return response()->json([
					'success' => false,
					'message' => 'El producto no está eliminado.',
				], 422);
			}
			/*
			* CONTROL DE DUPLICIDAD POR CÓDIGO.
			*/
			$existeCodigo = Producto::withTrashed()
				->whereRaw(
					'LOWER("codigo") = LOWER(?)',
					[$producto->codigo]
				)
				->where(
					'idProducto',
					'<>',
					$producto->idProducto
				)
				->exists();
			if ($existeCodigo) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar el producto porque ya existe otro con el mismo código.',
				], 422);
			}
			/*
			* CONTROL DE DUPLICIDAD POR DESCRIPCIÓN.
			*/
			$existeDescripcion = Producto::withTrashed()
				->whereRaw(
					'LOWER("descripcion") = LOWER(?)',
					[$producto->descripcion]
				)
				->where(
					'idProducto',
					'<>',
					$producto->idProducto
				)
				->exists();
			if ($existeDescripcion) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar el producto porque ya existe otro con la misma descripción.',
				], 422);
			}
			/*
			* La unidad de medida debe seguir existiendo
			* y estar activa.
			*/
			$unidadMedida = UnidadMedida::find(
				$producto->idUnidadMedida
			);
			if (!$unidadMedida) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar el producto porque la unidad de medida ya no existe.',
				], 422);
			}
			if (!$unidadMedida->activo) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar el producto porque la unidad de medida se encuentra inactiva.',
				], 422);
			}
			/*
			* El usuario de registro debe seguir existiendo
			* y estar activo.
			*/
			$usuarioRegistro = Usuario::find(
				$producto->idUsuarioRegistro
			);
			if (!$usuarioRegistro || !$usuarioRegistro->activo) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar el producto porque el usuario de registro no está activo.',
				], 422);
			}
			$producto->restore();
			$producto->activo = true;
			$producto->fechaBaja = null;
			$producto->IdUsuarioBaja = null;
			$producto->save();
			$producto->load([
				'unidadMedida:idUnidadMedida,codigo,descripcion,factorBase,activo',
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			]);
			return response()->json([
				'success' => true,
				'message' => 'Producto restaurado correctamente.',
				'data' => $producto,
			]);
		});
	}
}
