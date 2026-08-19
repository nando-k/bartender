<?php
namespace App\Http\Controllers\Almacen;
use App\Http\Controllers\Controller;
use App\Http\Requests\Almacen\StoreProductoPresentacionRequest;
use App\Http\Requests\Almacen\UpdateProductoPresentacionRequest;
use App\Models\Almacen\Producto;
use App\Models\Almacen\ProductoPresentacion;
use App\Models\Seguridad\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ProductoPresentacionController extends Controller
{
	public function index(Request $request): JsonResponse
	{
		$query = ProductoPresentacion::query()
			->with([
				'producto:idProducto,idUnidadMedida,descripcion,codigo,activo',
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			])
			->orderBy(
				'idProductoPresentacion',
				'desc'
			);
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
					'LOWER("nombre") LIKE LOWER(?)',
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
		if ($request->filled('nombre')) {
			$nombre = trim(
				$request->input('nombre')
			);
			$query->whereRaw(
				'LOWER("nombre") = LOWER(?)',
				[$nombre]
			);
		}
		if ($request->filled('idProducto')) {
			$query->where(
				'idProducto',
				$request->integer('idProducto')
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
		$presentaciones = $query->paginate(
			$request->integer('perPage', 15)
		);
		return response()->json([
			'success' => true,
			'message' => 'Presentaciones de productos obtenidas correctamente.',
			'data' => $presentaciones,
		]);
	}
	public function store(
		StoreProductoPresentacionRequest $request
	): JsonResponse {
		return DB::transaction(function () use ($request) {
			$idProducto = $request->integer(
				'idProducto'
			);
			$nombre = trim(
				$request->input('nombre')
			);
			$codigo = trim(
				$request->input('codigo')
			);
			$cantidadBase = $request->input(
				'cantidadBase'
			);
			$precio = $request->input(
				'precio'
			);
			$idUsuarioRegistro = $request->integer(
				'idUsuarioRegistro'
			);
			/*
			* Verificamos el producto.
			*/
			$producto = Producto::find(
				$idProducto
			);
			if (!$producto) {
				return response()->json([
					'success' => false,
					'message' => 'El producto no existe.',
					'errors' => [
						'idProducto' => [
							'El producto indicado no está disponible.'
						],
					],
				], 422);
			}
			if (!$producto->activo) {
				return response()->json([
					'success' => false,
					'message' => 'El producto se encuentra inactivo.',
					'errors' => [
						'idProducto' => [
							'No se puede registrar una presentación para un producto inactivo.'
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
							'No se puede registrar una presentación con un usuario inactivo.'
						],
					],
				], 422);
			}
			/*
			* =========================================================
			* CONTROL DE DUPLICIDAD
			* =========================================================
			*
			* El código debe ser único dentro del producto.
			*
			* Ejemplo:
			*
			* Producto 1 -> BOT-001
			* Producto 1 -> BOT-001  ❌
			* Producto 2 -> BOT-001  ✅
			*/
			$existeCodigo = ProductoPresentacion::withTrashed()
				->where(
					'idProducto',
					$idProducto
				)
				->whereRaw(
					'LOWER("codigo") = LOWER(?)',
					[$codigo]
				)
				->exists();
			if ($existeCodigo) {
				return response()->json([
					'success' => false,
					'message' => 'El código de la presentación ya se encuentra registrado para este producto.',
					'errors' => [
						'codigo' => [
							'Ya existe una presentación con este código para el producto seleccionado.'
						],
					],
				], 422);
			}
			/*
			* El nombre también debe ser único dentro
			* del producto.
			*/
			$existeNombre = ProductoPresentacion::withTrashed()
				->where(
					'idProducto',
					$idProducto
				)
				->whereRaw(
					'LOWER("nombre") = LOWER(?)',
					[$nombre]
				)
				->exists();
			if ($existeNombre) {
				return response()->json([
					'success' => false,
					'message' => 'El nombre de la presentación ya se encuentra registrado para este producto.',
					'errors' => [
						'nombre' => [
							'Ya existe una presentación con este nombre para el producto seleccionado.'
						],
					],
				], 422);
			}
			$presentacion = new ProductoPresentacion();
			$presentacion->idProducto = $idProducto;
			$presentacion->nombre = $nombre;
			$presentacion->codigo = $codigo;
			$presentacion->cantidadBase = $cantidadBase;
			$presentacion->precio = $precio;
			$presentacion->fechaRegistro = $request->input(
				'fechaRegistro',
				now()
			);
			$presentacion->idUsuarioRegistro = $idUsuarioRegistro;
			$presentacion->fechaBaja = null;
			$presentacion->IdUsuarioBaja = null;
			$presentacion->activo = $request->has('activo')
				? $request->boolean('activo')
				: true;
			$presentacion->save();
			$presentacion->load([
				'producto:idProducto,idUnidadMedida,descripcion,codigo,activo',
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			]);
			return response()->json([
				'success' => true,
				'message' => 'Presentación de producto registrada correctamente.',
				'data' => $presentacion,
			], 201);
		});
	}
	public function show(
		int $idProductoPresentacion
	): JsonResponse {
		$presentacion = ProductoPresentacion::with([
			'producto:idProducto,idUnidadMedida,descripcion,codigo,activo',
			'usuarioRegistro:idUsuario,cuenta,activo',
			'usuarioBaja:idUsuario,cuenta,activo',
		])->find(
			$idProductoPresentacion
		);
		if (!$presentacion) {
			return response()->json([
				'success' => false,
				'message' => 'Presentación de producto no encontrada.',
			], 404);
		}
		return response()->json([
			'success' => true,
			'message' => 'Presentación de producto obtenida correctamente.',
			'data' => $presentacion,
		]);
	}
	public function update(
		UpdateProductoPresentacionRequest $request,
		int $idProductoPresentacion
	): JsonResponse {
		return DB::transaction(function () use (
			$request,
			$idProductoPresentacion
		) {
			$presentacion = ProductoPresentacion::find(
				$idProductoPresentacion
			);
			if (!$presentacion) {
				return response()->json([
					'success' => false,
					'message' => 'Presentación de producto no encontrada.',
				], 404);
			}
			$idProducto = $request->integer(
				'idProducto'
			);
			$nombre = trim(
				$request->input('nombre')
			);
			$codigo = trim(
				$request->input('codigo')
			);
			/*
			* Verificamos el producto.
			*/
			$producto = Producto::find(
				$idProducto
			);
			if (!$producto) {
				return response()->json([
					'success' => false,
					'message' => 'El producto no existe.',
					'errors' => [
						'idProducto' => [
							'El producto indicado no está disponible.'
						],
					],
				], 422);
			}
			if (!$producto->activo) {
				return response()->json([
					'success' => false,
					'message' => 'El producto se encuentra inactivo.',
					'errors' => [
						'idProducto' => [
							'No se puede actualizar una presentación para un producto inactivo.'
						],
					],
				], 422);
			}
			/*
			* Verificamos el usuario de registro.
			*/
			$idUsuarioRegistro = $request->integer(
				'idUsuarioRegistro'
			);
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
							'No se puede actualizar una presentación con un usuario inactivo.'
						],
					],
				], 422);
			}
			/*
			* =========================================================
			* CONTROL DE DUPLICIDAD POR CÓDIGO
			* =========================================================
			*/
			$existeCodigo = ProductoPresentacion::withTrashed()
				->where(
					'idProducto',
					$idProducto
				)
				->whereRaw(
					'LOWER("codigo") = LOWER(?)',
					[$codigo]
				)
				->where(
					'idProductoPresentacion',
					'<>',
					$presentacion->idProductoPresentacion
				)
				->exists();
			if ($existeCodigo) {
				return response()->json([
					'success' => false,
					'message' => 'El código de la presentación ya se encuentra registrado para este producto.',
					'errors' => [
						'codigo' => [
							'Ya existe otra presentación con este código para el producto seleccionado.'
						],
					],
				], 422);
			}
			/*
			* =========================================================
			* CONTROL DE DUPLICIDAD POR NOMBRE
			* =========================================================
			*/
			$existeNombre = ProductoPresentacion::withTrashed()
				->where(
					'idProducto',
					$idProducto
				)
				->whereRaw(
					'LOWER("nombre") = LOWER(?)',
					[$nombre]
				)
				->where(
					'idProductoPresentacion',
					'<>',
					$presentacion->idProductoPresentacion
				)
				->exists();
			if ($existeNombre) {
				return response()->json([
					'success' => false,
					'message' => 'El nombre de la presentación ya se encuentra registrado para este producto.',
					'errors' => [
						'nombre' => [
							'Ya existe otra presentación con este nombre para el producto seleccionado.'
						],
					],
				], 422);
			}
			$presentacion->idProducto = $idProducto;
			$presentacion->nombre = $nombre;
			$presentacion->codigo = $codigo;
			$presentacion->cantidadBase = $request->input(
				'cantidadBase'
			);
			$presentacion->precio = $request->input(
				'precio'
			);
			if ($request->filled('fechaRegistro')) {
				$presentacion->fechaRegistro = $request->input(
					'fechaRegistro'
				);
			}
			$presentacion->idUsuarioRegistro = $idUsuarioRegistro;
			if ($request->has('fechaBaja')) {
				$presentacion->fechaBaja = $request->input(
					'fechaBaja'
				);
			}
			if ($request->has('IdUsuarioBaja')) {
				$presentacion->IdUsuarioBaja = $request->input(
					'IdUsuarioBaja'
				);
			}
			if ($request->has('activo')) {
				$presentacion->activo = $request->boolean(
					'activo'
				);
			}
			$presentacion->save();
			$presentacion->load([
				'producto:idProducto,idUnidadMedida,descripcion,codigo,activo',
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			]);
			return response()->json([
				'success' => true,
				'message' => 'Presentación de producto actualizada correctamente.',
				'data' => $presentacion,
			]);
		});
	}
	public function destroy(
		int $idProductoPresentacion
	): JsonResponse {
		return DB::transaction(function () use (
			$idProductoPresentacion
		) {
			$presentacion = ProductoPresentacion::find(
				$idProductoPresentacion
			);
			if (!$presentacion) {
				return response()->json([
					'success' => false,
					'message' => 'Presentación de producto no encontrada.',
				], 404);
			}
			$presentacion->activo = false;
			$presentacion->fechaBaja = now();
			$presentacion->save();
			$presentacion->delete();
			return response()->json([
				'success' => true,
				'message' => 'Presentación de producto eliminada correctamente.',
			]);
		});
	}
	public function restore(
		int $idProductoPresentacion
	): JsonResponse {
		return DB::transaction(function () use (
			$idProductoPresentacion
		) {
			$presentacion = ProductoPresentacion::withTrashed()
				->find(
					$idProductoPresentacion
				);
			if (!$presentacion) {
				return response()->json([
					'success' => false,
					'message' => 'Presentación de producto no encontrada.',
				], 404);
			}
			if (!$presentacion->trashed()) {
				return response()->json([
					'success' => false,
					'message' => 'La presentación de producto no está eliminada.',
				], 422);
			}
			/*
			* Verificamos que el producto siga existiendo.
			*/
			$producto = Producto::find(
				$presentacion->idProducto
			);
			if (!$producto) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar la presentación porque el producto ya no existe.',
				], 422);
			}
			if (!$producto->activo) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar la presentación porque el producto está inactivo.',
				], 422);
			}
			/*
			* Verificamos duplicidad de código.
			*/
			$existeCodigo = ProductoPresentacion::withTrashed()
				->where(
					'idProducto',
					$presentacion->idProducto
				)
				->whereRaw(
					'LOWER("codigo") = LOWER(?)',
					[$presentacion->codigo]
				)
				->where(
					'idProductoPresentacion',
					'<>',
					$presentacion->idProductoPresentacion
				)
				->exists();
			if ($existeCodigo) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar la presentación porque ya existe otra con el mismo código para este producto.',
				], 422);
			}
			/*
			* Verificamos duplicidad de nombre.
			*/
			$existeNombre = ProductoPresentacion::withTrashed()
				->where(
					'idProducto',
					$presentacion->idProducto
				)
				->whereRaw(
					'LOWER("nombre") = LOWER(?)',
					[$presentacion->nombre]
				)
				->where(
					'idProductoPresentacion',
					'<>',
					$presentacion->idProductoPresentacion
				)
				->exists();
			if ($existeNombre) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar la presentación porque ya existe otra con el mismo nombre para este producto.',
				], 422);
			}
			/*
			* Verificamos usuario de registro.
			*/
			$usuarioRegistro = Usuario::find(
				$presentacion->idUsuarioRegistro
			);
			if (!$usuarioRegistro || !$usuarioRegistro->activo) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar la presentación porque el usuario de registro no está activo.',
				], 422);
			}
			$presentacion->restore();
			$presentacion->activo = true;
			$presentacion->fechaBaja = null;
			$presentacion->IdUsuarioBaja = null;
			$presentacion->save();
			$presentacion->load([
				'producto:idProducto,idUnidadMedida,descripcion,codigo,activo',
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			]);
			return response()->json([
				'success' => true,
				'message' => 'Presentación de producto restaurada correctamente.',
				'data' => $presentacion,
			]);
		});
	}
}
