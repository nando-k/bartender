<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\StoreRolRequest;
use App\Http\Requests\Seguridad\UpdateRolRequest;
use App\Models\Seguridad\Rol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RolController extends Controller
{
	public function index(Request $request): JsonResponse
	{
		$query = Rol::query()
			->withCount('rolUsuarios')
			->orderBy('idRol', 'desc');

		if ($request->filled('buscar')) {
			$buscar = trim($request->input('buscar'));

			$query->where(
				'nombre',
				'ILIKE',
				"%{$buscar}%"
			);
		}

		if ($request->has('activo')) {
			$query->where(
				'activo',
				$request->boolean('activo')
			);
		}

		$roles = $query->paginate(
			$request->integer('perPage', 15)
		);

		return response()->json([
			'success' => true,
			'message' => 'Roles obtenidos correctamente.',
			'data' => $roles,
		]);
	}

	public function store(StoreRolRequest $request): JsonResponse
	{
		return DB::transaction(function () use ($request) {

			$nombre = trim($request->input('nombre'));

			/*
			* Control de duplicidad.
			*
			* Se utiliza withTrashed() para impedir que se
			* registre nuevamente un rol que fue eliminado
			* lógicamente.
			*
			* LOWER permite que:
			*
			* Administrador
			* administrador
			* ADMINISTRADOR
			*
			* sean considerados el mismo rol.
			*/
			$existe = Rol::withTrashed()
				->whereRaw(
					'LOWER("nombre") = LOWER(?)',
					[$nombre]
				)
				->exists();

			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'El rol ya se encuentra registrado.',
					'errors' => [
						'nombre' => [
							'Ya existe un rol con este nombre.'
						],
					],
				], 422);
			}

			$rol = new Rol();

			$rol->nombre = $nombre;

			$rol->fechaRegistro = $request->input(
				'fechaRegistro',
				now()
			);

			$rol->fechaBaja = null;

			$rol->activo = $request->has('activo')
				? $request->boolean('activo')
				: true;

			$rol->save();

			return response()->json([
				'success' => true,
				'message' => 'Rol registrado correctamente.',
				'data' => $rol,
			], 201);
		});
	}

	public function show(int $idRol): JsonResponse
	{
		$rol = Rol::withCount('rolUsuarios')
			->find($idRol);

		if (!$rol) {
			return response()->json([
				'success' => false,
				'message' => 'Rol no encontrado.',
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' => 'Rol obtenido correctamente.',
			'data' => $rol,
		]);
	}

	public function update(
		UpdateRolRequest $request,
		int $idRol
	): JsonResponse {
		return DB::transaction(function () use (
			$request,
			$idRol
		) {

			$rol = Rol::find($idRol);

			if (!$rol) {
				return response()->json([
					'success' => false,
					'message' => 'Rol no encontrado.',
				], 404);
			}

			$nombre = trim($request->input('nombre'));

			/*
			* Control de duplicidad en actualización.
			*
			* Se excluye el propio registro.
			*/
			$existe = Rol::withTrashed()
				->whereRaw(
					'LOWER("nombre") = LOWER(?)',
					[$nombre]
				)
				->where(
					'idRol',
					'<>',
					$rol->idRol
				)
				->exists();

			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'El rol ya se encuentra registrado.',
					'errors' => [
						'nombre' => [
							'Ya existe otro rol con este nombre.'
						],
					],
				], 422);
			}

			$rol->nombre = $nombre;

			if ($request->filled('fechaRegistro')) {
				$rol->fechaRegistro = $request->input(
					'fechaRegistro'
				);
			}

			if ($request->has('fechaBaja')) {
				$rol->fechaBaja = $request->input(
					'fechaBaja'
				);
			}

			if ($request->has('activo')) {
				$rol->activo = $request->boolean('activo');
			}

			$rol->save();

			return response()->json([
				'success' => true,
				'message' => 'Rol actualizado correctamente.',
				'data' => $rol,
			]);
		});
	}

	public function destroy(int $idRol): JsonResponse
	{
		return DB::transaction(function () use ($idRol) {

			$rol = Rol::find($idRol);

			if (!$rol) {
				return response()->json([
					'success' => false,
					'message' => 'Rol no encontrado.',
				], 404);
			}

			/*
			* Primero registramos la baja funcional.
			*/
			$rol->activo = false;
			$rol->fechaBaja = now();
			$rol->save();

			/*
			* Después realizamos el SoftDelete.
			*/
			$rol->delete();

			return response()->json([
				'success' => true,
				'message' => 'Rol eliminado correctamente.',
			]);
		});
	}

	public function restore(int $idRol): JsonResponse
	{
		return DB::transaction(function () use ($idRol) {

			$rol = Rol::withTrashed()
				->find($idRol);

			if (!$rol) {
				return response()->json([
					'success' => false,
					'message' => 'Rol no encontrado.',
				], 404);
			}

			if (!$rol->trashed()) {
				return response()->json([
					'success' => false,
					'message' => 'El rol no está eliminado.',
				], 422);
			}

			/*
			* Antes de restaurar verificamos que no exista
			* otro rol con el mismo nombre.
			*/
			$existe = Rol::withTrashed()
				->whereRaw(
					'LOWER("nombre") = LOWER(?)',
					[$rol->nombre]
				)
				->where(
					'idRol',
					'<>',
					$rol->idRol
				)
				->exists();

			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar el rol porque ya existe otro rol con el mismo nombre.',
				], 422);
			}

			$rol->restore();

			$rol->activo = true;
			$rol->fechaBaja = null;
			$rol->save();

			return response()->json([
				'success' => true,
				'message' => 'Rol restaurado correctamente.',
				'data' => $rol,
			]);
		});
	}
}
