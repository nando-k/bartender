<?php

namespace App\Http\Controllers\Parametro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametro\StoreTipoRequest;
use App\Http\Requests\Parametro\UpdateTipoRequest;
use App\Models\Parametro\Tipo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TipoController extends Controller
{
	public function index(Request $request): JsonResponse
	{
		$query = Tipo::query()
			->withCount('detalles')
			->orderBy('idTipo', 'desc');

		if ($request->filled('buscar')) {
			$buscar = trim($request->input('buscar'));

			$query->where(function ($q) use ($buscar) {
				$q->whereRaw(
					'LOWER("nombre") LIKE LOWER(?)',
					["%{$buscar}%"]
				)
				->orWhereRaw(
					'LOWER("descripcion") LIKE LOWER(?)',
					["%{$buscar}%"]
				);
			});
		}

		if ($request->has('activo')) {
			$query->where(
				'activo',
				$request->boolean('activo')
			);
		}

		$tipos = $query->paginate(
			$request->integer('perPage', 15)
		);

		return response()->json([
			'success' => true,
			'message' => 'Tipos obtenidos correctamente.',
			'data' => $tipos,
		]);
	}

	public function store(StoreTipoRequest $request): JsonResponse
	{
		return DB::transaction(function () use ($request) {

			$nombre = trim($request->input('nombre'));

			/*
			* CONTROL DE DUPLICIDAD.
			*
			* No permitimos:
			*
			* "Producto"
			* "producto"
			* " PRODUCTO "
			*
			* como registros diferentes.
			*
			* withTrashed() también considera registros
			* eliminados mediante SoftDelete.
			*/
			$existe = Tipo::withTrashed()
				->whereRaw(
					'LOWER("nombre") = LOWER(?)',
					[$nombre]
				)
				->exists();

			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'El tipo ya se encuentra registrado.',
					'errors' => [
						'nombre' => [
							'Ya existe un tipo con este nombre.'
						],
					],
				], 422);
			}

			$tipo = new Tipo();

			$tipo->nombre = $nombre;

			$tipo->descripcion = $request->filled('descripcion')
				? trim($request->input('descripcion'))
				: null;

			$tipo->fechaRegistro = $request->input(
				'fechaRegistro',
				now()
			);

			$tipo->fechaBaja = null;

			$tipo->activo = $request->has('activo')
				? $request->boolean('activo')
				: true;

			$tipo->save();

			return response()->json([
				'success' => true,
				'message' => 'Tipo registrado correctamente.',
				'data' => $tipo,
			], 201);
		});
	}

	public function show(int $idTipo): JsonResponse
	{
		$tipo = Tipo::withCount('detalles')
			->find($idTipo);

		if (!$tipo) {
			return response()->json([
				'success' => false,
				'message' => 'Tipo no encontrado.',
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' => 'Tipo obtenido correctamente.',
			'data' => $tipo,
		]);
	}

	public function update(
		UpdateTipoRequest $request,
		int $idTipo
	): JsonResponse {
		return DB::transaction(function () use (
			$request,
			$idTipo
		) {

			$tipo = Tipo::find($idTipo);

			if (!$tipo) {
				return response()->json([
					'success' => false,
					'message' => 'Tipo no encontrado.',
				], 404);
			}

			$nombre = trim($request->input('nombre'));

			/*
			* CONTROL DE DUPLICIDAD EN UPDATE.
			*
			* Excluimos el registro que estamos modificando.
			*/
			$existe = Tipo::withTrashed()
				->whereRaw(
					'LOWER("nombre") = LOWER(?)',
					[$nombre]
				)
				->where(
					'idTipo',
					'<>',
					$tipo->idTipo
				)
				->exists();

			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'El tipo ya se encuentra registrado.',
					'errors' => [
						'nombre' => [
							'Ya existe otro tipo con este nombre.'
						],
					],
				], 422);
			}

			$tipo->nombre = $nombre;

			if ($request->has('descripcion')) {
				$tipo->descripcion = $request->filled('descripcion')
					? trim($request->input('descripcion'))
					: null;
			}

			if ($request->filled('fechaRegistro')) {
				$tipo->fechaRegistro = $request->input(
					'fechaRegistro'
				);
			}

			if ($request->has('fechaBaja')) {
				$tipo->fechaBaja = $request->input(
					'fechaBaja'
				);
			}

			if ($request->has('activo')) {
				$tipo->activo = $request->boolean('activo');
			}

			$tipo->save();

			return response()->json([
				'success' => true,
				'message' => 'Tipo actualizado correctamente.',
				'data' => $tipo,
			]);
		});
	}

	public function destroy(int $idTipo): JsonResponse
	{
		return DB::transaction(function () use ($idTipo) {

			$tipo = Tipo::find($idTipo);

			if (!$tipo) {
				return response()->json([
					'success' => false,
					'message' => 'Tipo no encontrado.',
				], 404);
			}

			/*
			* No permitimos eliminar un tipo que todavía
			* tenga registros dependientes.
			*
			* La FK de la BD también protege la integridad,
			* pero hacemos el control aquí para devolver
			* un mensaje amigable.
			*/
			if ($tipo->detalles()->exists()) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede eliminar el tipo porque tiene registros asociados.',
				], 422);
			}

			$tipo->activo = false;
			$tipo->fechaBaja = now();

			$tipo->save();

			$tipo->delete();

			return response()->json([
				'success' => true,
				'message' => 'Tipo eliminado correctamente.',
			]);
		});
	}

	public function restore(int $idTipo): JsonResponse
	{
		return DB::transaction(function () use ($idTipo) {

			$tipo = Tipo::withTrashed()
				->find($idTipo);

			if (!$tipo) {
				return response()->json([
					'success' => false,
					'message' => 'Tipo no encontrado.',
				], 404);
			}

			if (!$tipo->trashed()) {
				return response()->json([
					'success' => false,
					'message' => 'El tipo no está eliminado.',
				], 422);
			}

			/*
			* CONTROL DE DUPLICIDAD ANTES DE RESTAURAR.
			*/
			$existe = Tipo::withTrashed()
				->whereRaw(
					'LOWER("nombre") = LOWER(?)',
					[$tipo->nombre]
				)
				->where(
					'idTipo',
					'<>',
					$tipo->idTipo
				)
				->exists();

			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar el tipo porque ya existe otro registro con el mismo nombre.',
				], 422);
			}

			$tipo->restore();

			$tipo->activo = true;
			$tipo->fechaBaja = null;

			$tipo->save();

			return response()->json([
				'success' => true,
				'message' => 'Tipo restaurado correctamente.',
				'data' => $tipo,
			]);
		});
	}
}
