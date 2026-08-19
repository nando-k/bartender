<?php

namespace App\Http\Controllers\Almacen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Almacen\StoreVentaRequest;
use App\Http\Requests\Almacen\UpdateVentaRequest;
use App\Models\Almacen\Cliente;
use App\Models\Almacen\Inventario;
use App\Models\Almacen\Venta;
use App\Models\Parametro\Detalle;
use App\Models\Seguridad\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
	/**
	 * Listar ventas.
	 */
	public function index(Request $request): JsonResponse
	{
		$query = Venta::query()
			->with([
				'inventario',
				'cliente',
				'tipo',
				'estado',
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			])
			->orderBy(
				'idVenta',
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
		| Filtro por cliente
		|--------------------------------------------------------------------------
		*/

		if ($request->filled('idCliente')) {
			$query->where(
				'idCliente',
				$request->integer(
					'idCliente'
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
		| Filtro por estado
		|--------------------------------------------------------------------------
		*/

		if ($request->filled('idEstado')) {
			$query->where(
				'idEstado',
				$request->integer(
					'idEstado'
				)
			);
		}

		/*
		|--------------------------------------------------------------------------
		| Filtro por estado activo
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

		$ventas = $query->paginate(
			$request->integer(
				'perPage',
				15
			)
		);

		return response()->json([
			'success' => true,
			'message' =>
				'Ventas obtenidas correctamente.',
			'data' => $ventas,
		]);
	}

	/**
	 * Registrar venta.
	 */
	public function store(
		StoreVentaRequest $request
	): JsonResponse {
		return DB::transaction(
			function () use ($request) {

				$idInventario =
					$request->integer(
						'idInventario'
					);

				$idCliente =
					$request->integer(
						'idCliente'
					);

				$idTipo =
					$request->integer(
						'idTipo'
					);

				$total =
					$request->input(
						'total'
					);

				$idEstado =
					$request->integer(
						'idEstado'
					);

				$fechaRegistro =
					$request->input(
						'fechaRegistro',
						now()
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
				| Verificar cliente
				|--------------------------------------------------------------------------
				*/

				$cliente = Cliente::find(
					$idCliente
				);

				if (!$cliente) {
					return response()->json([
						'success' => false,
						'message' =>
							'El cliente no existe o se encuentra eliminado.',
						'errors' => [
							'idCliente' => [
								'El cliente indicado no está disponible.'
							],
						],
					], 422);
				}

				if (!$cliente->activo) {
					return response()->json([
						'success' => false,
						'message' =>
							'El cliente se encuentra inactivo.',
						'errors' => [
							'idCliente' => [
								'No se puede registrar una venta para un cliente inactivo.'
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
							'El tipo de venta no existe.',
						'errors' => [
							'idTipo' => [
								'El tipo indicado no está disponible.'
							],
						],
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Verificar estado
				|--------------------------------------------------------------------------
				*/

				$estado = Detalle::find(
					$idEstado
				);

				if (!$estado) {
					return response()->json([
						'success' => false,
						'message' =>
							'El estado de la venta no existe.',
						'errors' => [
							'idEstado' => [
								'El estado indicado no está disponible.'
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
								'No se puede registrar una venta con un usuario inactivo.'
							],
						],
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| CONTROL DE DUPLICIDAD
				|--------------------------------------------------------------------------
				|
				| Una venta no se considera duplicada únicamente por
				| cliente o inventario.
				|
				| Controlamos la combinación:
				|
				| idInventario
				| idCliente
				| idTipo
				| total
				| idEstado
				| fechaRegistro
				|
				| withTrashed() hace que también se considere un registro
				| eliminado lógicamente.
				|
				*/

				$existeVenta = Venta::withTrashed()
					->where(
						'idInventario',
						$idInventario
					)
					->where(
						'idCliente',
						$idCliente
					)
					->where(
						'idTipo',
						$idTipo
					)
					->where(
						'total',
						$total
					)
					->where(
						'idEstado',
						$idEstado
					)
					->where(
						'fechaRegistro',
						$fechaRegistro
					)
					->exists();

				if ($existeVenta) {
					return response()->json([
						'success' => false,
						'message' =>
							'La venta ya se encuentra registrada.',
						'errors' => [
							'idCliente' => [
								'Ya existe una venta con los mismos datos.'
							],
						],
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Crear venta
				|--------------------------------------------------------------------------
				*/

				$venta = new Venta();

				$venta->idInventario =
					$idInventario;

				$venta->idCliente =
					$idCliente;

				$venta->idTipo =
					$idTipo;

				$venta->total =
					$total;

				$venta->idEstado =
					$idEstado;

				$venta->fechaRegistro =
					$fechaRegistro;

				$venta->idUsuarioRegistro =
					$idUsuarioRegistro;

				$venta->fechaBaja = null;

				$venta->IdUsuarioBaja = null;

				$venta->activo = true;

				$venta->save();

				/*
				|--------------------------------------------------------------------------
				| Relaciones
				|--------------------------------------------------------------------------
				*/

				$venta->load([
					'inventario',
					'cliente',
					'tipo',
					'estado',
					'usuarioRegistro:idUsuario,cuenta,activo',
				]);

				return response()->json([
					'success' => true,
					'message' =>
						'Venta registrada correctamente.',
					'data' => $venta,
				], 201);
			}
		);
	}

	/**
	 * Mostrar venta.
	 */
	public function show(
		int $idVenta
	): JsonResponse {
		$venta = Venta::with([
			'inventario',
			'cliente',
			'tipo',
			'estado',
			'usuarioRegistro:idUsuario,cuenta,activo',
			'usuarioBaja:idUsuario,cuenta,activo',
			'detalles',
		])->find(
			$idVenta
		);

		if (!$venta) {
			return response()->json([
				'success' => false,
				'message' =>
					'Venta no encontrada.',
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' =>
				'Venta obtenida correctamente.',
			'data' => $venta,
		]);
	}

	/**
	 * Actualizar venta.
	 */
	public function update(
		UpdateVentaRequest $request,
		int $idVenta
	): JsonResponse {
		return DB::transaction(
			function () use (
				$request,
				$idVenta
			) {

				$venta = Venta::find(
					$idVenta
				);

				if (!$venta) {
					return response()->json([
						'success' => false,
						'message' =>
							'Venta no encontrada.',
					], 404);
				}

				$idInventario =
					$request->integer(
						'idInventario'
					);

				$idCliente =
					$request->integer(
						'idCliente'
					);

				$idTipo =
					$request->integer(
						'idTipo'
					);

				$total =
					$request->input(
						'total'
					);

				$idEstado =
					$request->integer(
						'idEstado'
					);

				$fechaRegistro =
					$request->input(
						'fechaRegistro',
						$venta->fechaRegistro
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

				if (!Inventario::find($idInventario)) {
					return response()->json([
						'success' => false,
						'message' =>
							'El inventario no existe o se encuentra eliminado.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Verificar cliente
				|--------------------------------------------------------------------------
				*/

				$cliente = Cliente::find(
					$idCliente
				);

				if (!$cliente) {
					return response()->json([
						'success' => false,
						'message' =>
							'El cliente no existe o se encuentra eliminado.',
					], 422);
				}

				if (!$cliente->activo) {
					return response()->json([
						'success' => false,
						'message' =>
							'El cliente se encuentra inactivo.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Verificar tipo
				|--------------------------------------------------------------------------
				*/

				if (!Detalle::find($idTipo)) {
					return response()->json([
						'success' => false,
						'message' =>
							'El tipo de venta no existe.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Verificar estado
				|--------------------------------------------------------------------------
				*/

				if (!Detalle::find($idEstado)) {
					return response()->json([
						'success' => false,
						'message' =>
							'El estado de la venta no existe.',
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
					], 422);
				}

				if (!$usuarioRegistro->activo) {
					return response()->json([
						'success' => false,
						'message' =>
							'El usuario de registro se encuentra inactivo.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| CONTROL DE DUPLICIDAD
				|--------------------------------------------------------------------------
				*/

				$existeVenta = Venta::withTrashed()
					->where(
						'idInventario',
						$idInventario
					)
					->where(
						'idCliente',
						$idCliente
					)
					->where(
						'idTipo',
						$idTipo
					)
					->where(
						'total',
						$total
					)
					->where(
						'idEstado',
						$idEstado
					)
					->where(
						'fechaRegistro',
						$fechaRegistro
					)
					->where(
						'idVenta',
						'<>',
						$venta->idVenta
					)
					->exists();

				if ($existeVenta) {
					return response()->json([
						'success' => false,
						'message' =>
							'La venta ya se encuentra registrada.',
						'errors' => [
							'idCliente' => [
								'Ya existe otra venta con los mismos datos.'
							],
						],
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Actualizar
				|--------------------------------------------------------------------------
				*/

				$venta->idInventario =
					$idInventario;

				$venta->idCliente =
					$idCliente;

				$venta->idTipo =
					$idTipo;

				$venta->total =
					$total;

				$venta->idEstado =
					$idEstado;

				$venta->fechaRegistro =
					$fechaRegistro;

				$venta->idUsuarioRegistro =
					$idUsuarioRegistro;

				if ($request->has('fechaBaja')) {
					$venta->fechaBaja =
						$request->input(
							'fechaBaja'
						);
				}

				if ($request->has('IdUsuarioBaja')) {
					$venta->IdUsuarioBaja =
						$request->input(
							'IdUsuarioBaja'
						);
				}

				if ($request->has('activo')) {
					$venta->activo =
						$request->boolean(
							'activo'
						);
				}

				$venta->save();

				$venta->load([
					'inventario',
					'cliente',
					'tipo',
					'estado',
					'usuarioRegistro:idUsuario,cuenta,activo',
					'usuarioBaja:idUsuario,cuenta,activo',
				]);

				return response()->json([
					'success' => true,
					'message' =>
						'Venta actualizada correctamente.',
					'data' => $venta,
				]);
			}
		);
	}

	/**
	 * Eliminar venta mediante SoftDelete.
	 */
	public function destroy(
		int $idVenta
	): JsonResponse {
		return DB::transaction(
			function () use ($idVenta) {

				$venta = Venta::find(
					$idVenta
				);

				if (!$venta) {
					return response()->json([
						'success' => false,
						'message' =>
							'Venta no encontrada.',
					], 404);
				}

				/*
				|--------------------------------------------------------------------------
				| Baja lógica propia
				|--------------------------------------------------------------------------
				*/

				$venta->activo = false;

				$venta->fechaBaja = now();

				$venta->save();

				/*
				|--------------------------------------------------------------------------
				| SoftDelete Laravel
				|--------------------------------------------------------------------------
				*/

				$venta->delete();

				return response()->json([
					'success' => true,
					'message' =>
						'Venta eliminada correctamente.',
				]);
			}
		);
	}

	/**
	 * Restaurar venta.
	 */
	public function restore(
		int $idVenta
	): JsonResponse {
		return DB::transaction(
			function () use ($idVenta) {

				$venta = Venta::withTrashed()
					->find(
						$idVenta
					);

				if (!$venta) {
					return response()->json([
						'success' => false,
						'message' =>
							'Venta no encontrada.',
					], 404);
				}

				if (!$venta->trashed()) {
					return response()->json([
						'success' => false,
						'message' =>
							'La venta no está eliminada.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Verificar inventario
				|--------------------------------------------------------------------------
				*/

				if (!Inventario::find(
					$venta->idInventario
				)) {
					return response()->json([
						'success' => false,
						'message' =>
							'No se puede restaurar la venta porque el inventario ya no existe.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Verificar cliente
				|--------------------------------------------------------------------------
				*/

				$cliente = Cliente::find(
					$venta->idCliente
				);

				if (!$cliente) {
					return response()->json([
						'success' => false,
						'message' =>
							'No se puede restaurar la venta porque el cliente ya no existe.',
					], 422);
				}

				if (!$cliente->activo) {
					return response()->json([
						'success' => false,
						'message' =>
							'No se puede restaurar la venta porque el cliente está inactivo.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Verificar tipo
				|--------------------------------------------------------------------------
				*/

				if (!Detalle::find(
					$venta->idTipo
				)) {
					return response()->json([
						'success' => false,
						'message' =>
							'No se puede restaurar la venta porque el tipo ya no existe.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Verificar estado
				|--------------------------------------------------------------------------
				*/

				if (!Detalle::find(
					$venta->idEstado
				)) {
					return response()->json([
						'success' => false,
						'message' =>
							'No se puede restaurar la venta porque el estado ya no existe.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| CONTROL DE DUPLICIDAD
				|--------------------------------------------------------------------------
				*/

				$existeVenta = Venta::withTrashed()
					->where(
						'idInventario',
						$venta->idInventario
					)
					->where(
						'idCliente',
						$venta->idCliente
					)
					->where(
						'idTipo',
						$venta->idTipo
					)
					->where(
						'total',
						$venta->total
					)
					->where(
						'idEstado',
						$venta->idEstado
					)
					->where(
						'fechaRegistro',
						$venta->fechaRegistro
					)
					->where(
						'idVenta',
						'<>',
						$venta->idVenta
					)
					->exists();

				if ($existeVenta) {
					return response()->json([
						'success' => false,
						'message' =>
							'No se puede restaurar la venta porque ya existe otra venta con los mismos datos.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Usuario de registro
				|--------------------------------------------------------------------------
				*/

				$usuarioRegistro = Usuario::find(
					$venta->idUsuarioRegistro
				);

				if (!$usuarioRegistro) {
					return response()->json([
						'success' => false,
						'message' =>
							'No se puede restaurar la venta porque el usuario de registro ya no existe.',
					], 422);
				}

				if (!$usuarioRegistro->activo) {
					return response()->json([
						'success' => false,
						'message' =>
							'No se puede restaurar la venta porque el usuario de registro está inactivo.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Restaurar
				|--------------------------------------------------------------------------
				*/

				$venta->restore();

				$venta->activo = true;

				$venta->fechaBaja = null;

				$venta->IdUsuarioBaja = null;

				$venta->save();

				$venta->load([
					'inventario',
					'cliente',
					'tipo',
					'estado',
					'usuarioRegistro:idUsuario,cuenta,activo',
					'usuarioBaja:idUsuario,cuenta,activo',
				]);

				return response()->json([
					'success' => true,
					'message' =>
						'Venta restaurada correctamente.',
					'data' => $venta,
				]);
			}
		);
	}
}
