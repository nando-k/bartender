<?php

namespace App\Http\Controllers\Almacen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Almacen\StoreInventarioRequest;
use App\Http\Requests\Almacen\UpdateInventarioRequest;
use App\Models\Almacen\Inventario;
use App\Models\Almacen\Producto;
use App\Models\Seguridad\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioController extends Controller
{
	/**
	 * Mostrar listado de inventarios.
	 */
	public function index(Request $request): JsonResponse
	{
		$query = Inventario::query()
			->with([
				'producto:idProducto,idUnidadMedida,descripcion,codigo,activo',
				'usuarioRegistro:idUsuario,cuenta,activo',
			])
			->orderBy(
				'idInventario',
				'desc'
			);

		/*
		|--------------------------------------------------------------------------
		| Filtro por producto
		|--------------------------------------------------------------------------
		*/

		if ($request->filled('idProducto')) {
			$query->where(
				'idProducto',
				$request->integer('idProducto')
			);
		}

		/*
		|--------------------------------------------------------------------------
		| Filtro por búsqueda
		|--------------------------------------------------------------------------
		|
		| Busca por código o descripción del producto.
		|
		*/

		if ($request->filled('buscar')) {
			$buscar = trim(
				$request->input('buscar')
			);

			$query->whereHas(
				'producto',
				function ($q) use ($buscar) {
					$q->whereRaw(
						'LOWER("codigo") LIKE LOWER(?)',
						["%{$buscar}%"]
					)
					->orWhereRaw(
						'LOWER("descripcion") LIKE LOWER(?)',
						["%{$buscar}%"]
					);
				}
			);
		}

		/*
		|--------------------------------------------------------------------------
		| Filtro por usuario de registro
		|--------------------------------------------------------------------------
		*/

		if ($request->filled('idUsuarioRegistro')) {
			$query->where(
				'idUsuarioRegistro',
				$request->integer('idUsuarioRegistro')
			);
		}

		/*
		|--------------------------------------------------------------------------
		| Paginación
		|--------------------------------------------------------------------------
		*/

		$inventarios = $query->paginate(
			$request->integer(
				'perPage',
				15
			)
		);

		return response()->json([
			'success' => true,
			'message' => 'Inventarios obtenidos correctamente.',
			'data' => $inventarios,
		]);
	}

	/**
	 * Registrar inventario.
	 */
	public function store(
		StoreInventarioRequest $request
	): JsonResponse {
		return DB::transaction(
			function () use ($request) {

				$idProducto = $request->integer(
					'idProducto'
				);

				$idUsuarioRegistro = $request->integer(
					'idUsuarioRegistro'
				);

				/*
				|--------------------------------------------------------------------------
				| Verificar producto
				|--------------------------------------------------------------------------
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

				/*
				|--------------------------------------------------------------------------
				| El inventario solamente debe utilizar productos activos.
				|--------------------------------------------------------------------------
				*/

				if (!$producto->activo) {
					return response()->json([
						'success' => false,
						'message' => 'El producto se encuentra inactivo.',
						'errors' => [
							'idProducto' => [
								'No se puede registrar inventario para un producto inactivo.'
							],
						],
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Verificar usuario de registro
				|--------------------------------------------------------------------------
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
								'No se puede registrar inventario con un usuario inactivo.'
							],
						],
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| CONTROL DE DUPLICIDAD
				|--------------------------------------------------------------------------
				|
				| Un producto solamente puede tener un registro de inventario.
				|
				| withTrashed() es intencional.
				|
				| Si existe un inventario eliminado lógicamente para el producto,
				| tampoco permitimos crear otro.
				|
				*/

				$existeInventario = Inventario::withTrashed()
					->where(
						'idProducto',
						$idProducto
					)
					->exists();

				if ($existeInventario) {
					return response()->json([
						'success' => false,
						'message' => 'El producto ya tiene un registro de inventario.',
						'errors' => [
							'idProducto' => [
								'Ya existe un inventario registrado para el producto seleccionado.'
							],
						],
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Crear inventario
				|--------------------------------------------------------------------------
				*/

				$inventario = new Inventario();

				$inventario->idProducto = $idProducto;

				$inventario->precioUnitario = $request->input(
					'precioUnitario'
				);

				$inventario->cantidad = $request->input(
					'cantidad'
				);

				$inventario->precioTotal = $request->input(
					'precioTotal'
				);

				$inventario->fechaRegistro = $request->input(
					'fechaRegistro',
					now()
				);

				$inventario->idUsuarioRegistro = $idUsuarioRegistro;

				$inventario->save();

				/*
				|--------------------------------------------------------------------------
				| Cargar relaciones
				|--------------------------------------------------------------------------
				*/

				$inventario->load([
					'producto:idProducto,idUnidadMedida,descripcion,codigo,activo',
					'usuarioRegistro:idUsuario,cuenta,activo',
				]);

				return response()->json([
					'success' => true,
					'message' => 'Inventario registrado correctamente.',
					'data' => $inventario,
				], 201);
			}
		);
	}

	/**
	 * Mostrar un inventario.
	 */
	public function show(
		int $idInventario
	): JsonResponse {
		$inventario = Inventario::with([
			'producto:idProducto,idUnidadMedida,descripcion,codigo,activo',
			'usuarioRegistro:idUsuario,cuenta,activo',
		])->find(
			$idInventario
		);

		if (!$inventario) {
			return response()->json([
				'success' => false,
				'message' => 'Inventario no encontrado.',
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' => 'Inventario obtenido correctamente.',
			'data' => $inventario,
		]);
	}

	/**
	 * Actualizar inventario.
	 */
	public function update(
		UpdateInventarioRequest $request,
		int $idInventario
	): JsonResponse {
		return DB::transaction(
			function () use (
				$request,
				$idInventario
			) {

				$inventario = Inventario::find(
					$idInventario
				);

				if (!$inventario) {
					return response()->json([
						'success' => false,
						'message' => 'Inventario no encontrado.',
					], 404);
				}

				$idProducto = $request->integer(
					'idProducto'
				);

				$idUsuarioRegistro = $request->integer(
					'idUsuarioRegistro'
				);

				/*
				|--------------------------------------------------------------------------
				| Verificar producto
				|--------------------------------------------------------------------------
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
								'No se puede actualizar el inventario de un producto inactivo.'
							],
						],
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Verificar usuario
				|--------------------------------------------------------------------------
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
								'No se puede actualizar inventario con un usuario inactivo.'
							],
						],
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| CONTROL DE DUPLICIDAD
				|--------------------------------------------------------------------------
				|
				| Excluimos el registro actual.
				|
				*/

				$existeInventario = Inventario::withTrashed()
					->where(
						'idProducto',
						$idProducto
					)
					->where(
						'idInventario',
						'<>',
						$inventario->idInventario
					)
					->exists();

				if ($existeInventario) {
					return response()->json([
						'success' => false,
						'message' => 'El producto ya tiene otro registro de inventario.',
						'errors' => [
							'idProducto' => [
								'Ya existe otro inventario registrado para el producto seleccionado.'
							],
						],
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Actualizar
				|--------------------------------------------------------------------------
				*/

				$inventario->idProducto = $idProducto;

				$inventario->precioUnitario = $request->input(
					'precioUnitario'
				);

				$inventario->cantidad = $request->input(
					'cantidad'
				);

				$inventario->precioTotal = $request->input(
					'precioTotal'
				);

				if ($request->filled('fechaRegistro')) {
					$inventario->fechaRegistro = $request->input(
						'fechaRegistro'
					);
				}

				$inventario->idUsuarioRegistro =
					$idUsuarioRegistro;

				$inventario->save();

				/*
				|--------------------------------------------------------------------------
				| Cargar relaciones
				|--------------------------------------------------------------------------
				*/

				$inventario->load([
					'producto:idProducto,idUnidadMedida,descripcion,codigo,activo',
					'usuarioRegistro:idUsuario,cuenta,activo',
				]);

				return response()->json([
					'success' => true,
					'message' => 'Inventario actualizado correctamente.',
					'data' => $inventario,
				]);
			}
		);
	}

	/**
	 * Eliminar inventario mediante SoftDelete.
	 */
	public function destroy(
		int $idInventario
	): JsonResponse {
		return DB::transaction(
			function () use ($idInventario) {

				$inventario = Inventario::find(
					$idInventario
				);

				if (!$inventario) {
					return response()->json([
						'success' => false,
						'message' => 'Inventario no encontrado.',
					], 404);
				}

				/*
				|--------------------------------------------------------------------------
				| SoftDelete
				|--------------------------------------------------------------------------
				*/

				$inventario->delete();

				return response()->json([
					'success' => true,
					'message' => 'Inventario eliminado correctamente.',
				]);
			}
		);
	}

	/**
	 * Restaurar inventario.
	 */
	public function restore(
		int $idInventario
	): JsonResponse {
		return DB::transaction(
			function () use ($idInventario) {

				/*
				|--------------------------------------------------------------------------
				| Buscar incluyendo eliminados
				|--------------------------------------------------------------------------
				*/

				$inventario = Inventario::withTrashed()
					->find(
						$idInventario
					);

				if (!$inventario) {
					return response()->json([
						'success' => false,
						'message' => 'Inventario no encontrado.',
					], 404);
				}

				if (!$inventario->trashed()) {
					return response()->json([
						'success' => false,
						'message' => 'El inventario no está eliminado.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Verificar producto
				|--------------------------------------------------------------------------
				*/

				$producto = Producto::find(
					$inventario->idProducto
				);

				if (!$producto) {
					return response()->json([
						'success' => false,
						'message' => 'No se puede restaurar el inventario porque el producto ya no existe.',
					], 422);
				}

				if (!$producto->activo) {
					return response()->json([
						'success' => false,
						'message' => 'No se puede restaurar el inventario porque el producto está inactivo.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| CONTROL DE DUPLICIDAD AL RESTAURAR
				|--------------------------------------------------------------------------
				|
				| Buscamos incluso registros eliminados.
				|
				*/

				$existeInventario = Inventario::withTrashed()
					->where(
						'idProducto',
						$inventario->idProducto
					)
					->where(
						'idInventario',
						'<>',
						$inventario->idInventario
					)
					->exists();

				if ($existeInventario) {
					return response()->json([
						'success' => false,
						'message' => 'No se puede restaurar el inventario porque ya existe otro registro para el mismo producto.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Verificar usuario de registro
				|--------------------------------------------------------------------------
				*/

				$usuarioRegistro = Usuario::find(
					$inventario->idUsuarioRegistro
				);

				if (!$usuarioRegistro) {
					return response()->json([
						'success' => false,
						'message' => 'No se puede restaurar el inventario porque el usuario de registro ya no existe.',
					], 422);
				}

				if (!$usuarioRegistro->activo) {
					return response()->json([
						'success' => false,
						'message' => 'No se puede restaurar el inventario porque el usuario de registro está inactivo.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Restaurar
				|--------------------------------------------------------------------------
				*/

				$inventario->restore();

				$inventario->load([
					'producto:idProducto,idUnidadMedida,descripcion,codigo,activo',
					'usuarioRegistro:idUsuario,cuenta,activo',
				]);

				return response()->json([
					'success' => true,
					'message' => 'Inventario restaurado correctamente.',
					'data' => $inventario,
				]);
			}
		);
	}
}
