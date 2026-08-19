<?php

namespace App\Http\Controllers\Almacen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Almacen\StoreClienteRequest;
use App\Http\Requests\Almacen\UpdateClienteRequest;
use App\Models\Almacen\Cliente;
use App\Models\Seguridad\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClienteController extends Controller
{
	/**
	 * Listar clientes.
	 */
	public function index(Request $request): JsonResponse
	{
		$query = Cliente::query()
			->with([
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			])
			->orderBy(
				'idCliente',
				'desc'
			);

		/*
		|--------------------------------------------------------------------------
		| Buscar por razón social o documento
		|--------------------------------------------------------------------------
		*/

		if ($request->filled('buscar')) {
			$buscar = trim(
				$request->input('buscar')
			);

			$query->where(function ($q) use ($buscar) {
				$q->whereRaw(
					'LOWER("razonSocial") LIKE LOWER(?)',
					["%{$buscar}%"]
				)
				->orWhereRaw(
					'LOWER("numeroDocumento") LIKE LOWER(?)',
					["%{$buscar}%"]
				);
			});
		}

		/*
		|--------------------------------------------------------------------------
		| Filtro exacto por documento
		|--------------------------------------------------------------------------
		*/

		if ($request->filled('numeroDocumento')) {
			$numeroDocumento = trim(
				$request->input('numeroDocumento')
			);

			$query->whereRaw(
				'LOWER("numeroDocumento") = LOWER(?)',
				[$numeroDocumento]
			);
		}

		/*
		|--------------------------------------------------------------------------
		| Filtro por razón social
		|--------------------------------------------------------------------------
		*/

		if ($request->filled('razonSocial')) {
			$razonSocial = trim(
				$request->input('razonSocial')
			);

			$query->whereRaw(
				'LOWER("razonSocial") = LOWER(?)',
				[$razonSocial]
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
		| Filtro por usuario de registro
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
		| Paginación
		|--------------------------------------------------------------------------
		*/

		$clientes = $query->paginate(
			$request->integer(
				'perPage',
				15
			)
		);

		return response()->json([
			'success' => true,
			'message' => 'Clientes obtenidos correctamente.',
			'data' => $clientes,
		]);
	}

	/**
	 * Registrar cliente.
	 */
	public function store(
		StoreClienteRequest $request
	): JsonResponse {
		return DB::transaction(
			function () use ($request) {

				$razonSocial = trim(
					$request->input('razonSocial')
				);

				$numeroDocumento = trim(
					$request->input('numeroDocumento')
				);

				$idUsuarioRegistro = $request->integer(
					'idUsuarioRegistro'
				);

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
								'No se puede registrar un cliente con un usuario inactivo.'
							],
						],
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| CONTROL DE DUPLICIDAD POR NÚMERO DE DOCUMENTO
				|--------------------------------------------------------------------------
				|
				| Se utiliza withTrashed() para que un registro eliminado
				| lógicamente también sea considerado duplicado.
				|
				*/

				$existeDocumento = Cliente::withTrashed()
					->whereRaw(
						'LOWER("numeroDocumento") = LOWER(?)',
						[$numeroDocumento]
					)
					->exists();

				if ($existeDocumento) {
					return response()->json([
						'success' => false,
						'message' =>
							'El número de documento/NIT ya se encuentra registrado.',
						'errors' => [
							'numeroDocumento' => [
								'Ya existe un cliente registrado con este número de documento/NIT.'
							],
						],
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| CONTROL DE DUPLICIDAD POR RAZÓN SOCIAL
				|--------------------------------------------------------------------------
				|
				| También evitamos dos clientes con exactamente la misma
				| razón social, sin distinguir mayúsculas/minúsculas.
				|
				*/

				$existeRazonSocial = Cliente::withTrashed()
					->whereRaw(
						'LOWER("razonSocial") = LOWER(?)',
						[$razonSocial]
					)
					->exists();

				if ($existeRazonSocial) {
					return response()->json([
						'success' => false,
						'message' =>
							'La razón social ya se encuentra registrada.',
						'errors' => [
							'razonSocial' => [
								'Ya existe un cliente registrado con esta razón social.'
							],
						],
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Crear cliente
				|--------------------------------------------------------------------------
				*/

				$cliente = new Cliente();

				$cliente->razonSocial = $razonSocial;

				$cliente->numeroDocumento =
					$numeroDocumento;

				$cliente->fechaRegistro =
					$request->input(
						'fechaRegistro',
						now()
					);

				$cliente->idUsuarioRegistro =
					$idUsuarioRegistro;

				$cliente->fechaBaja = null;

				$cliente->IdUsuarioBaja = null;

				$cliente->activo =
					$request->has('activo')
						? $request->boolean('activo')
						: true;

				$cliente->save();

				/*
				|--------------------------------------------------------------------------
				| Cargar relaciones
				|--------------------------------------------------------------------------
				*/

				$cliente->load([
					'usuarioRegistro:idUsuario,cuenta,activo',
					'usuarioBaja:idUsuario,cuenta,activo',
				]);

				return response()->json([
					'success' => true,
					'message' =>
						'Cliente registrado correctamente.',
					'data' => $cliente,
				], 201);
			}
		);
	}

	/**
	 * Mostrar cliente.
	 */
	public function show(
		int $idCliente
	): JsonResponse {
		$cliente = Cliente::with([
			'usuarioRegistro:idUsuario,cuenta,activo',
			'usuarioBaja:idUsuario,cuenta,activo',
		])->find(
			$idCliente
		);

		if (!$cliente) {
			return response()->json([
				'success' => false,
				'message' =>
					'Cliente no encontrado.',
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' =>
				'Cliente obtenido correctamente.',
			'data' => $cliente,
		]);
	}

	/**
	 * Actualizar cliente.
	 */
	public function update(
		UpdateClienteRequest $request,
		int $idCliente
	): JsonResponse {
		return DB::transaction(
			function () use (
				$request,
				$idCliente
			) {

				$cliente = Cliente::find(
					$idCliente
				);

				if (!$cliente) {
					return response()->json([
						'success' => false,
						'message' =>
							'Cliente no encontrado.',
					], 404);
				}

				$razonSocial = trim(
					$request->input('razonSocial')
				);

				$numeroDocumento = trim(
					$request->input('numeroDocumento')
				);

				$idUsuarioRegistro = $request->integer(
					'idUsuarioRegistro'
				);

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
								'No se puede actualizar un cliente con un usuario inactivo.'
							],
						],
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| CONTROL DE DUPLICIDAD POR DOCUMENTO
				|--------------------------------------------------------------------------
				|
				| Excluimos al cliente que estamos actualizando.
				|
				*/

				$existeDocumento = Cliente::withTrashed()
					->whereRaw(
						'LOWER("numeroDocumento") = LOWER(?)',
						[$numeroDocumento]
					)
					->where(
						'idCliente',
						'<>',
						$cliente->idCliente
					)
					->exists();

				if ($existeDocumento) {
					return response()->json([
						'success' => false,
						'message' =>
							'El número de documento/NIT ya se encuentra registrado.',
						'errors' => [
							'numeroDocumento' => [
								'Ya existe otro cliente registrado con este número de documento/NIT.'
							],
						],
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| CONTROL DE DUPLICIDAD POR RAZÓN SOCIAL
				|--------------------------------------------------------------------------
				*/

				$existeRazonSocial = Cliente::withTrashed()
					->whereRaw(
						'LOWER("razonSocial") = LOWER(?)',
						[$razonSocial]
					)
					->where(
						'idCliente',
						'<>',
						$cliente->idCliente
					)
					->exists();

				if ($existeRazonSocial) {
					return response()->json([
						'success' => false,
						'message' =>
							'La razón social ya se encuentra registrada.',
						'errors' => [
							'razonSocial' => [
								'Ya existe otro cliente registrado con esta razón social.'
							],
						],
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Actualizar
				|--------------------------------------------------------------------------
				*/

				$cliente->razonSocial =
					$razonSocial;

				$cliente->numeroDocumento =
					$numeroDocumento;

				if ($request->filled('fechaRegistro')) {
					$cliente->fechaRegistro =
						$request->input(
							'fechaRegistro'
						);
				}

				$cliente->idUsuarioRegistro =
					$idUsuarioRegistro;

				if ($request->has('fechaBaja')) {
					$cliente->fechaBaja =
						$request->input(
							'fechaBaja'
						);
				}

				if ($request->has('IdUsuarioBaja')) {
					$cliente->IdUsuarioBaja =
						$request->input(
							'IdUsuarioBaja'
						);
				}

				if ($request->has('activo')) {
					$cliente->activo =
						$request->boolean(
							'activo'
						);
				}

				$cliente->save();

				/*
				|--------------------------------------------------------------------------
				| Cargar relaciones
				|--------------------------------------------------------------------------
				*/

				$cliente->load([
					'usuarioRegistro:idUsuario,cuenta,activo',
					'usuarioBaja:idUsuario,cuenta,activo',
				]);

				return response()->json([
					'success' => true,
					'message' =>
						'Cliente actualizado correctamente.',
					'data' => $cliente,
				]);
			}
		);
	}

	/**
	 * Eliminar cliente mediante SoftDelete.
	 */
	public function destroy(
		int $idCliente
	): JsonResponse {
		return DB::transaction(
			function () use ($idCliente) {

				$cliente = Cliente::find(
					$idCliente
				);

				if (!$cliente) {
					return response()->json([
						'success' => false,
						'message' =>
							'Cliente no encontrado.',
					], 404);
				}

				/*
				|--------------------------------------------------------------------------
				| Baja lógica propia de la tabla
				|--------------------------------------------------------------------------
				*/

				$cliente->activo = false;

				$cliente->fechaBaja = now();

				/*
				* En una baja normal no establecemos
				* IdUsuarioBaja porque el endpoint no
				* recibe ese dato.
				*
				* Si posteriormente quieres que el usuario
				* autenticado sea registrado automáticamente,
				* podemos tomarlo de Auth::id().
				*/

				$cliente->save();

				/*
				|--------------------------------------------------------------------------
				| SoftDelete de Laravel
				|--------------------------------------------------------------------------
				*/

				$cliente->delete();

				return response()->json([
					'success' => true,
					'message' =>
						'Cliente eliminado correctamente.',
				]);
			}
		);
	}

	/**
	 * Restaurar cliente.
	 */
	public function restore(
		int $idCliente
	): JsonResponse {
		return DB::transaction(
			function () use ($idCliente) {

				/*
				|--------------------------------------------------------------------------
				| Buscar incluyendo eliminados
				|--------------------------------------------------------------------------
				*/

				$cliente = Cliente::withTrashed()
					->find(
						$idCliente
					);

				if (!$cliente) {
					return response()->json([
						'success' => false,
						'message' =>
							'Cliente no encontrado.',
					], 404);
				}

				if (!$cliente->trashed()) {
					return response()->json([
						'success' => false,
						'message' =>
							'El cliente no está eliminado.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| CONTROL DE DUPLICIDAD POR DOCUMENTO
				|--------------------------------------------------------------------------
				*/

				$existeDocumento = Cliente::withTrashed()
					->whereRaw(
						'LOWER("numeroDocumento") = LOWER(?)',
						[$cliente->numeroDocumento]
					)
					->where(
						'idCliente',
						'<>',
						$cliente->idCliente
					)
					->exists();

				if ($existeDocumento) {
					return response()->json([
						'success' => false,
						'message' =>
							'No se puede restaurar el cliente porque ya existe otro cliente con el mismo número de documento/NIT.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| CONTROL DE DUPLICIDAD POR RAZÓN SOCIAL
				|--------------------------------------------------------------------------
				*/

				$existeRazonSocial = Cliente::withTrashed()
					->whereRaw(
						'LOWER("razonSocial") = LOWER(?)',
						[$cliente->razonSocial]
					)
					->where(
						'idCliente',
						'<>',
						$cliente->idCliente
					)
					->exists();

				if ($existeRazonSocial) {
					return response()->json([
						'success' => false,
						'message' =>
							'No se puede restaurar el cliente porque ya existe otro cliente con la misma razón social.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Verificar usuario de registro
				|--------------------------------------------------------------------------
				*/

				$usuarioRegistro = Usuario::find(
					$cliente->idUsuarioRegistro
				);

				if (!$usuarioRegistro) {
					return response()->json([
						'success' => false,
						'message' =>
							'No se puede restaurar el cliente porque el usuario de registro ya no existe.',
					], 422);
				}

				if (!$usuarioRegistro->activo) {
					return response()->json([
						'success' => false,
						'message' =>
							'No se puede restaurar el cliente porque el usuario de registro está inactivo.',
					], 422);
				}

				/*
				|--------------------------------------------------------------------------
				| Restaurar
				|--------------------------------------------------------------------------
				*/

				$cliente->restore();

				$cliente->activo = true;

				$cliente->fechaBaja = null;

				$cliente->IdUsuarioBaja = null;

				$cliente->save();

				$cliente->load([
					'usuarioRegistro:idUsuario,cuenta,activo',
					'usuarioBaja:idUsuario,cuenta,activo',
				]);

				return response()->json([
					'success' => true,
					'message' =>
						'Cliente restaurado correctamente.',
					'data' => $cliente,
				]);
			}
		);
	}
}
