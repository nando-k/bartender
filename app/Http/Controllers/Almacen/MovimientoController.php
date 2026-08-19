<?php

namespace App\Http\Controllers\Almacen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Almacen\StoreMovimientoRequest;
use App\Http\Requests\Almacen\UpdateMovimientoRequest;
use App\Models\Almacen\Inventario;
use App\Models\Almacen\Movimiento;
use App\Models\Parametro\Detalle;
use App\Models\Seguridad\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MovimientoController extends Controller
{
	/**
	 * Listar movimientos.
	 */
	public function index(Request $request): JsonResponse
	{
		$query = Movimiento::query()
			->with([
				'inventario',
				'tipo',
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			])
			->orderBy(
				'idMovimiento',
				'desc'
			);

		/*
		|--------------------------------------------------------------------------
		| Filtro por inventario
		|--------------------------------------------------------------------------
		*/

		if ($request->filled('idInventario')) {
			$query->where(
				'idInventario',
				$request->integer(
					'idInventario'
				)
			);
		}

		/*
		|--------------------------------------------------------------------------
		| Filtro por tipo
		|--------------------------------------------------------------------------
		*/

		if ($request->filled('idTipo')) {
			$query->where(
				'idTipo',
				$request->integer(
					'idTipo'
				)
			);
		}

		/*
		|--------------------------------------------------------------------------
		| Filtro por usuario
		|--------------------------------------------------------------------------
		*/

		if ($request->filled('idUsuarioRegistro')) {
			$query->where(
				'idUsuarioRegistro',
				$request->integer(
					'idUsuarioRegistro'
				)
			);
		}

		/*
		|--------------------------------------------------------------------------
		| Filtro por estado
		|--------------------------------------------------------------------------
		*/

		if ($request->has('activo')) {
			$query->where(
				'activo',
				$request->boolean('activo')
			);
		}

		/*
		|--------------------------------------------------------------------------
		| Filtro por fecha
		|--------------------------------------------------------------------------
		*/

		if ($request->filled('fechaDesde')) {
			$query->whereDate(
				'fechaRegistro',
				'>=',
				$request->input('fechaDesde')
			);
		}

		if ($request->filled('fechaHasta')) {
			$query->whereDate(
				'fechaRegistro',
				'<=',
				$request->input('fechaHasta')
			);
		}

		/*
		|--------------------------------------------------------------------------
		| Paginación
		|--------------------------------------------------------------------------
		*/

		$movimientos = $query->paginate(
			$request->integer(
				'perPage',
				15
			)
		);

		return response()->json([
			'success' => true,
			'message' =>
				'Movimientos obtenidos correctamente.',
			'data' => $movimientos,
		]);
	}

	/**
	 * Registrar movimiento.
	 */
	public function store(
		StoreMovimientoRequest $request
	): JsonResponse {
		return DB::transaction(
			function () use ($request) {

				$idInventario = $request->integer(
					'idInventario'
				);

				$idTipo = $request->integer(
					'idTipo'
				);

				$cantidad = $request->input(
					'cantidad'
				);

				$idUsuarioRegistro =
					$request->integer(
						'idUsuarioRegistro'
					);

				$fechaRegistro =
					$request->input(
						'fechaRegistro',
						now()
					);

				/*
				|--------------------------------------------------------------------------
				| Verificar inventario
				|--------------------------------------------------------------------------
				*/

				$inventario = Inventario::find(
					$idInventario
				);

				if (!$inventario) {
					return response()->json([
						'success' => false,
						'message' =>
							'El inventario no existe o se encuentra eliminado.',
						'errors' => [
							'idInventario' => [
								'El inventario indicado no está disponible.'
							],
						],
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Verificar tipo
				|--------------------------------------------------------------------------
				*/

				$tipo = Detalle::find(
					$idTipo
				);

				if (!$tipo) {
					return response()->json([
						'success' => false,
						'message' =>
							'El tipo de movimiento no existe.',
						'errors' => [
							'idTipo' => [
								'El tipo indicado no está disponible.'
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
						'message' =>
							'El usuario de registro no existe o se encuentra eliminado.',
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
						'message' =>
							'El usuario de registro se encuentra inactivo.',
						'errors' => [
							'idUsuarioRegistro' => [
								'No se puede registrar un movimiento con un usuario inactivo.'
							],
						],
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| CONTROL DE DUPLICIDAD
				|--------------------------------------------------------------------------
				|
				| No se considera duplicado solamente el idInventario,
				| porque un inventario puede tener muchos movimientos.
				|
				| Se controla la combinación:
				|
				| idInventario + idTipo + cantidad + fechaRegistro
				|
				| Se incluyen registros eliminados mediante withTrashed().
				|
				*/

				$existeMovimiento = Movimiento::withTrashed()
					->where(
						'idInventario',
						$idInventario
					)
					->where(
						'idTipo',
						$idTipo
					)
					->where(
						'cantidad',
						$cantidad
					)
					->where(
						'fechaRegistro',
						$fechaRegistro
					)
					->exists();

				if ($existeMovimiento) {
					return response()->json([
						'success' => false,
						'message' =>
							'El movimiento ya se encuentra registrado.',
						'errors' => [
							'idInventario' => [
								'Ya existe un movimiento con los mismos datos para este inventario.'
							],
						],
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Crear movimiento
				|--------------------------------------------------------------------------
				*/

				$movimiento = new Movimiento();

				$movimiento->idInventario =
					$idInventario;

				$movimiento->idTipo =
					$idTipo;

				$movimiento->cantidad =
					$cantidad;

				$movimiento->fechaRegistro =
					$fechaRegistro;

				$movimiento->idUsuarioRegistro =
					$idUsuarioRegistro;

				$movimiento->fechaBaja = null;

				$movimiento->IdUsuarioBaja = null;

				$movimiento->activo = true;

				$movimiento->save();

				/*
				|--------------------------------------------------------------------------
				| Cargar relaciones
				|--------------------------------------------------------------------------
				*/

				$movimiento->load([
					'inventario',
					'tipo',
					'usuarioRegistro:idUsuario,cuenta,activo',
				]);

				return response()->json([
					'success' => true,
					'message' =>
						'Movimiento registrado correctamente.',
					'data' => $movimiento,
				], 201);
			}
		);
	}

	/**
	 * Mostrar movimiento.
	 */
	public function show(
		int $idMovimiento
	): JsonResponse {
		$movimiento = Movimiento::with([
			'inventario',
			'tipo',
			'usuarioRegistro:idUsuario,cuenta,activo',
			'usuarioBaja:idUsuario,cuenta,activo',
		])->find(
			$idMovimiento
		);

		if (!$movimiento) {
			return response()->json([
				'success' => false,
				'message' =>
					'Movimiento no encontrado.',
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' =>
				'Movimiento obtenido correctamente.',
			'data' => $movimiento,
		]);
	}

	/**
	 * Actualizar movimiento.
	 */
	public function update(
		UpdateMovimientoRequest $request,
		int $idMovimiento
	): JsonResponse {
		return DB::transaction(
			function () use (
				$request,
				$idMovimiento
			) {

				$movimiento = Movimiento::find(
					$idMovimiento
				);

				if (!$movimiento) {
					return response()->json([
						'success' => false,
						'message' =>
							'Movimiento no encontrado.',
					], 404);
				}

				$idInventario =
					$request->integer(
						'idInventario'
					);

				$idTipo =
					$request->integer(
						'idTipo'
					);

				$cantidad =
					$request->input(
						'cantidad'
					);

				$idUsuarioRegistro =
					$request->integer(
						'idUsuarioRegistro'
					);

				/*
				|--------------------------------------------------------------------------
				| Verificar inventario
				|--------------------------------------------------------------------------
				*/

				$inventario = Inventario::find(
					$idInventario
				);

				if (!$inventario) {
					return response()->json([
						'success' => false,
						'message' =>
							'El inventario no existe o se encuentra eliminado.',
						'errors' => [
							'idInventario' => [
								'El inventario indicado no está disponible.'
							],
						],
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Verificar tipo
				|--------------------------------------------------------------------------
				*/

				$tipo = Detalle::find(
					$idTipo
				);

				if (!$tipo) {
					return response()->json([
						'success' => false,
						'message' =>
							'El tipo de movimiento no existe.',
						'errors' => [
							'idTipo' => [
								'El tipo indicado no está disponible.'
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
						'message' =>
							'El usuario de registro no existe o se encuentra eliminado.',
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
						'message' =>
							'El usuario de registro se encuentra inactivo.',
						'errors' => [
							'idUsuarioRegistro' => [
								'No se puede actualizar un movimiento con un usuario inactivo.'
							],
						],
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| CONTROL DE DUPLICIDAD
				|--------------------------------------------------------------------------
				|
				| Excluimos el movimiento actual.
				|
				*/

				$existeMovimiento = Movimiento::withTrashed()
					->where(
						'idInventario',
						$idInventario
					)
					->where(
						'idTipo',
						$idTipo
					)
					->where(
						'cantidad',
						$cantidad
					)
					->where(
						'fechaRegistro',
						$request->input(
							'fechaRegistro',
							$movimiento->fechaRegistro
						)
					)
					->where(
						'idMovimiento',
						'<>',
						$movimiento->idMovimiento
					)
					->exists();

				if ($existeMovimiento) {
					return response()->json([
						'success' => false,
						'message' =>
							'El movimiento ya se encuentra registrado.',
						'errors' => [
							'idInventario' => [
								'Ya existe otro movimiento con los mismos datos para este inventario.'
							],
						],
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Actualizar
				|--------------------------------------------------------------------------
				*/

				$movimiento->idInventario =
					$idInventario;

				$movimiento->idTipo =
					$idTipo;

				$movimiento->cantidad =
					$cantidad;

				if ($request->filled('fechaRegistro')) {
					$movimiento->fechaRegistro =
						$request->input(
							'fechaRegistro'
						);
				}

				$movimiento->idUsuarioRegistro =
					$idUsuarioRegistro;

				if ($request->has('fechaBaja')) {
					$movimiento->fechaBaja =
						$request->input(
							'fechaBaja'
						);
				}

				if ($request->has('IdUsuarioBaja')) {
					$movimiento->IdUsuarioBaja =
						$request->input(
							'IdUsuarioBaja'
						);
				}

				if ($request->has('activo')) {
					$movimiento->activo =
						$request->boolean(
							'activo'
						);
				}

				$movimiento->save();

				/*
				|--------------------------------------------------------------------------
				| Cargar relaciones
				|--------------------------------------------------------------------------
				*/

				$movimiento->load([
					'inventario',
					'tipo',
					'usuarioRegistro:idUsuario,cuenta,activo',
					'usuarioBaja:idUsuario,cuenta,activo',
				]);

				return response()->json([
					'success' => true,
					'message' =>
						'Movimiento actualizado correctamente.',
					'data' => $movimiento,
				]);
			}
		);
	}

	/**
	 * Eliminar movimiento mediante SoftDelete.
	 */
	public function destroy(
		int $idMovimiento
	): JsonResponse {
		return DB::transaction(
			function () use ($idMovimiento) {

				$movimiento = Movimiento::find(
					$idMovimiento
				);

				if (!$movimiento) {
					return response()->json([
						'success' => false,
						'message' =>
							'Movimiento no encontrado.',
					], 404);
				}

				/*
				|--------------------------------------------------------------------------
				| Baja lógica propia de la tabla
				|--------------------------------------------------------------------------
				*/

				$movimiento->activo = false;

				$movimiento->fechaBaja = now();

				$movimiento->save();

				/*
				|--------------------------------------------------------------------------
				| SoftDelete Laravel
				|--------------------------------------------------------------------------
				*/

				$movimiento->delete();

				return response()->json([
					'success' => true,
					'message' =>
						'Movimiento eliminado correctamente.',
				]);
			}
		);
	}

	/**
	 * Restaurar movimiento.
	 */
	public function restore(
		int $idMovimiento
	): JsonResponse {
		return DB::transaction(
			function () use ($idMovimiento) {

				/*
				|--------------------------------------------------------------------------
				| Buscar incluyendo eliminados
				|--------------------------------------------------------------------------
				*/

				$movimiento = Movimiento::withTrashed()
					->find(
						$idMovimiento
					);

				if (!$movimiento) {
					return response()->json([
						'success' => false,
						'message' =>
							'Movimiento no encontrado.',
					], 404);
				}

				if (!$movimiento->trashed()) {
					return response()->json([
						'success' => false,
						'message' =>
							'El movimiento no está eliminado.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Verificar inventario
				|--------------------------------------------------------------------------
				*/

				$inventario = Inventario::find(
					$movimiento->idInventario
				);

				if (!$inventario) {
					return response()->json([
						'success' => false,
						'message' =>
							'No se puede restaurar el movimiento porque el inventario ya no existe.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Verificar tipo
				|--------------------------------------------------------------------------
				*/

				$tipo = Detalle::find(
					$movimiento->idTipo
				);

				if (!$tipo) {
					return response()->json([
						'success' => false,
						'message' =>
							'No se puede restaurar el movimiento porque el tipo ya no existe.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| CONTROL DE DUPLICIDAD
				|--------------------------------------------------------------------------
				*/

				$existeMovimiento = Movimiento::withTrashed()
					->where(
						'idInventario',
						$movimiento->idInventario
					)
					->where(
						'idTipo',
						$movimiento->idTipo
					)
					->where(
						'cantidad',
						$movimiento->cantidad
					)
					->where(
						'fechaRegistro',
						$movimiento->fechaRegistro
					)
					->where(
						'idMovimiento',
						'<>',
						$movimiento->idMovimiento
					)
					->exists();

				if ($existeMovimiento) {
					return response()->json([
						'success' => false,
						'message' =>
							'No se puede restaurar el movimiento porque ya existe otro movimiento con los mismos datos.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Verificar usuario de registro
				|--------------------------------------------------------------------------
				*/

				$usuarioRegistro = Usuario::find(
					$movimiento->idUsuarioRegistro
				);

				if (!$usuarioRegistro) {
					return response()->json([
						'success' => false,
						'message' =>
							'No se puede restaurar el movimiento porque el usuario de registro ya no existe.',
					], 422);
				}

				if (!$usuarioRegistro->activo) {
					return response()->json([
						'success' => false,
						'message' =>
							'No se puede restaurar el movimiento porque el usuario de registro está inactivo.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Restaurar
				|--------------------------------------------------------------------------
				*/

				$movimiento->restore();

				$movimiento->activo = true;

				$movimiento->fechaBaja = null;

				$movimiento->IdUsuarioBaja = null;

				$movimiento->save();

				$movimiento->load([
					'inventario',
					'tipo',
					'usuarioRegistro:idUsuario,cuenta,activo',
					'usuarioBaja:idUsuario,cuenta,activo',
				]);

				return response()->json([
					'success' => true,
					'message' =>
						'Movimiento restaurado correctamente.',
					'data' => $movimiento,
				]);
			}
		);
	}
}
