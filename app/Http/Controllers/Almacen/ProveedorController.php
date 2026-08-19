<?php
namespace App\Http\Controllers\Almacen;
use App\Http\Controllers\Controller;
use App\Http\Requests\Almacen\StoreProveedorRequest;
use App\Http\Requests\Almacen\UpdateProveedorRequest;
use App\Models\Almacen\Proveedor;
use App\Models\Seguridad\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ProveedorController extends Controller
{
	public function index(Request $request): JsonResponse
	{
		$query = Proveedor::query()
			->with([
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			])
			->orderBy('idProveedor', 'desc');
		if ($request->filled('buscar')) {
			$buscar = trim($request->input('buscar'));
			$query->where(function ($q) use ($buscar) {
				$q->whereRaw(
					'LOWER("razonSocial") LIKE LOWER(?)',
					["%{$buscar}%"]
				)
				->orWhereRaw(
					'LOWER("representanteLegal") LIKE LOWER(?)',
					["%{$buscar}%"]
				)
				->orWhereRaw(
					'LOWER("nit") LIKE LOWER(?)',
					["%{$buscar}%"]
				);
			});
		}
		if ($request->filled('nit')) {
			$nit = trim($request->input('nit'));
			$query->where(
				'nit',
				$nit
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
		$proveedores = $query->paginate(
			$request->integer('perPage', 15)
		);
		return response()->json([
			'success' => true,
			'message' => 'Proveedores obtenidos correctamente.',
			'data' => $proveedores,
		]);
	}
	public function store(
		StoreProveedorRequest $request
	): JsonResponse {
		return DB::transaction(function () use ($request) {
			$razonSocial = trim(
				$request->input('razonSocial')
			);
			$representanteLegal = $request->filled(
				'representanteLegal'
			)
				? trim($request->input('representanteLegal'))
				: null;
			$nit = $request->filled('nit')
				? trim($request->input('nit'))
				: null;
			$idUsuarioRegistro = $request->integer(
				'idUsuarioRegistro'
			);
			/*
			* Verificamos que el usuario que registra
			* exista y esté activo.
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
							'No se puede registrar un proveedor con un usuario inactivo.'
						],
					],
				], 422);
			}
			/*
			* CONTROL DE DUPLICIDAD POR RAZÓN SOCIAL.
			*
			* withTrashed() incluye proveedores dados de baja
			* lógicamente.
			*/
			$existeRazonSocial = Proveedor::withTrashed()
				->whereRaw(
					'LOWER("razonSocial") = LOWER(?)',
					[$razonSocial]
				)
				->exists();
			if ($existeRazonSocial) {
				return response()->json([
					'success' => false,
					'message' => 'El proveedor ya se encuentra registrado.',
					'errors' => [
						'razonSocial' => [
							'Ya existe un proveedor con esta razón social.'
						],
					],
				], 422);
			}
			/*
			* CONTROL DE DUPLICIDAD POR NIT.
			*
			* El NIT es opcional. Solo controlamos duplicidad
			* cuando realmente fue proporcionado.
			*/
			if ($nit !== null) {
				$existeNit = Proveedor::withTrashed()
					->where('nit', $nit)
					->exists();
				if ($existeNit) {
					return response()->json([
						'success' => false,
						'message' => 'El NIT ya se encuentra registrado.',
						'errors' => [
							'nit' => [
								'Ya existe un proveedor registrado con este NIT.'
							],
						],
					], 422);
				}
			}
			$proveedor = new Proveedor();
			$proveedor->razonSocial = $razonSocial;
			$proveedor->representanteLegal = $representanteLegal;
			$proveedor->nit = $nit;
			$proveedor->fechaRegistro = $request->input(
				'fechaRegistro',
				now()
			);
			$proveedor->idUsuarioRegistro = $idUsuarioRegistro;
			$proveedor->fechaBaja = null;
			$proveedor->IdUsuarioBaja = null;
			$proveedor->activo = $request->has('activo')
				? $request->boolean('activo')
				: true;
			$proveedor->save();
			$proveedor->load([
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			]);
			return response()->json([
				'success' => true,
				'message' => 'Proveedor registrado correctamente.',
				'data' => $proveedor,
			], 201);
		});
	}
	public function show(int $idProveedor): JsonResponse
	{
		$proveedor = Proveedor::with([
			'usuarioRegistro:idUsuario,cuenta,activo',
			'usuarioBaja:idUsuario,cuenta,activo',
		])->find($idProveedor);
		if (!$proveedor) {
			return response()->json([
				'success' => false,
				'message' => 'Proveedor no encontrado.',
			], 404);
		}
		return response()->json([
			'success' => true,
			'message' => 'Proveedor obtenido correctamente.',
			'data' => $proveedor,
		]);
	}
	public function update(
		UpdateProveedorRequest $request,
		int $idProveedor
	): JsonResponse {
		return DB::transaction(function () use (
			$request,
			$idProveedor
		) {
			$proveedor = Proveedor::find(
				$idProveedor
			);
			if (!$proveedor) {
				return response()->json([
					'success' => false,
					'message' => 'Proveedor no encontrado.',
				], 404);
			}
			$razonSocial = trim(
				$request->input('razonSocial')
			);
			$representanteLegal = $request->filled(
				'representanteLegal'
			)
				? trim($request->input('representanteLegal'))
				: null;
			$nit = $request->filled('nit')
				? trim($request->input('nit'))
				: null;
			$idUsuarioRegistro = $request->integer(
				'idUsuarioRegistro'
			);
			/*
			* Verificamos que el usuario de registro
			* exista y esté activo.
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
							'No se puede actualizar el proveedor con un usuario inactivo.'
						],
					],
				], 422);
			}
			/*
			* CONTROL DE DUPLICIDAD DE RAZÓN SOCIAL.
			*/
			$existeRazonSocial = Proveedor::withTrashed()
				->whereRaw(
					'LOWER("razonSocial") = LOWER(?)',
					[$razonSocial]
				)
				->where(
					'idProveedor',
					'<>',
					$proveedor->idProveedor
				)
				->exists();
			if ($existeRazonSocial) {
				return response()->json([
					'success' => false,
					'message' => 'La razón social ya se encuentra registrada.',
					'errors' => [
						'razonSocial' => [
							'Ya existe otro proveedor con esta razón social.'
						],
					],
				], 422);
			}
			/*
			* CONTROL DE DUPLICIDAD DE NIT.
			*/
			if ($nit !== null) {
				$existeNit = Proveedor::withTrashed()
					->where('nit', $nit)
					->where(
						'idProveedor',
						'<>',
						$proveedor->idProveedor
					)
					->exists();
				if ($existeNit) {
					return response()->json([
						'success' => false,
						'message' => 'El NIT ya se encuentra registrado.',
						'errors' => [
							'nit' => [
								'Ya existe otro proveedor registrado con este NIT.'
							],
						],
					], 422);
				}
			}
			$proveedor->razonSocial = $razonSocial;
			$proveedor->representanteLegal = $representanteLegal;
			$proveedor->nit = $nit;
			if ($request->filled('fechaRegistro')) {
				$proveedor->fechaRegistro = $request->input(
					'fechaRegistro'
				);
			}
			$proveedor->idUsuarioRegistro = $idUsuarioRegistro;
			if ($request->has('fechaBaja')) {
				$proveedor->fechaBaja = $request->input(
					'fechaBaja'
				);
			}
			if ($request->has('IdUsuarioBaja')) {
				$proveedor->IdUsuarioBaja = $request->input(
					'IdUsuarioBaja'
				);
			}
			if ($request->has('activo')) {
				$proveedor->activo = $request->boolean(
					'activo'
				);
			}
			$proveedor->save();
			$proveedor->load([
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			]);
			return response()->json([
				'success' => true,
				'message' => 'Proveedor actualizado correctamente.',
				'data' => $proveedor,
			]);
		});
	}
	public function destroy(int $idProveedor): JsonResponse
	{
		return DB::transaction(function () use ($idProveedor) {
			$proveedor = Proveedor::find(
				$idProveedor
			);
			if (!$proveedor) {
				return response()->json([
					'success' => false,
					'message' => 'Proveedor no encontrado.',
				], 404);
			}
			$proveedor->activo = false;
			$proveedor->fechaBaja = now();
			/*
			* El usuario que ejecuta la baja debería venir
			* del usuario autenticado.
			*
			* Si posteriormente tenemos JWT/Auth,
			* aquí podemos reemplazarlo por:
			*
			* auth()->id()
			*
			* Por ahora dejamos el campo sin modificar.
			*/
			$proveedor->save();
			$proveedor->delete();
			return response()->json([
				'success' => true,
				'message' => 'Proveedor eliminado correctamente.',
			]);
		});
	}
	public function restore(int $idProveedor): JsonResponse
	{
		return DB::transaction(function () use ($idProveedor) {
			$proveedor = Proveedor::withTrashed()
				->find($idProveedor);
			if (!$proveedor) {
				return response()->json([
					'success' => false,
					'message' => 'Proveedor no encontrado.',
				], 404);
			}
			if (!$proveedor->trashed()) {
				return response()->json([
					'success' => false,
					'message' => 'El proveedor no está eliminado.',
				], 422);
			}
			/*
			* Verificamos nuevamente la razón social
			* antes de restaurar.
			*/
			$existeRazonSocial = Proveedor::withTrashed()
				->whereRaw(
					'LOWER("razonSocial") = LOWER(?)',
					[$proveedor->razonSocial]
				)
				->where(
					'idProveedor',
					'<>',
					$proveedor->idProveedor
				)
				->exists();
			if ($existeRazonSocial) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar el proveedor porque ya existe otro con la misma razón social.',
				], 422);
			}
			/*
			* Verificamos nuevamente el NIT si existe.
			*/
			if ($proveedor->nit !== null) {
				$existeNit = Proveedor::withTrashed()
					->where('nit', $proveedor->nit)
					->where(
						'idProveedor',
						'<>',
						$proveedor->idProveedor
					)
					->exists();
				if ($existeNit) {
					return response()->json([
						'success' => false,
						'message' => 'No se puede restaurar el proveedor porque el NIT ya pertenece a otro proveedor.',
					], 422);
				}
			}
			/*
			* Verificamos que el usuario que originalmente
			* registró el proveedor siga existiendo y activo.
			*/
			$usuarioRegistro = Usuario::find(
				$proveedor->idUsuarioRegistro
			);
			if (!$usuarioRegistro || !$usuarioRegistro->activo) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar el proveedor porque el usuario de registro no está activo.',
				], 422);
			}
			$proveedor->restore();
			$proveedor->activo = true;
			$proveedor->fechaBaja = null;
			$proveedor->IdUsuarioBaja = null;
			$proveedor->save();
			$proveedor->load([
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			]);
			return response()->json([
				'success' => true,
				'message' => 'Proveedor restaurado correctamente.',
				'data' => $proveedor,
			]);
		});
	}
}
