<?php

namespace App\Http\Controllers\Parametro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametro\StoreDetalleRequest;
use App\Http\Requests\Parametro\UpdateDetalleRequest;
use App\Models\Parametro\Detalle;
use App\Models\Parametro\Tipo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DetalleController extends Controller
{
	public function index(Request $request): JsonResponse
	{
		$query = Detalle::query()
			->with([
				'tipo:idTipo,nombre,activo',
			])
			->orderBy('idDetalle', 'desc');

		if ($request->filled('idTipo')) {
			$query->where(
				'idTipo',
				$request->integer('idTipo')
			);
		}

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

		$detalles = $query->paginate(
			$request->integer('perPage', 15)
		);

		return response()->json([
			'success' => true,
			'message' => 'Detalles obtenidos correctamente.',
			'data' => $detalles,
		]);
	}

	public function store(
		StoreDetalleRequest $request
	): JsonResponse {
		return DB::transaction(function () use ($request) {

			$idTipo = $request->integer('idTipo');

			$nombre = trim(
				$request->input('nombre')
			);

			/*
			* Verificamos que el tipo exista y esté activo.
			*/
			$tipo = Tipo::find($idTipo);

			if (!$tipo) {
				return response()->json([
					'success' => false,
					'message' => 'El tipo no existe o se encuentra eliminado.',
					'errors' => [
						'idTipo' => [
							'El tipo indicado no está disponible.'
						],
					],
				], 422);
			}

			if (!$tipo->activo) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede registrar un detalle en un tipo inactivo.',
					'errors' => [
						'idTipo' => [
							'El tipo se encuentra inactivo.'
						],
					],
				], 422);
			}

			/*
			* CONTROL DE DUPLICIDAD.
			*
			* La combinación lógica es:
			*
			* idTipo + nombre
			*
			* El mismo nombre puede existir en tipos diferentes.
			*/
			$existe = Detalle::withTrashed()
				->where('idTipo', $idTipo)
				->whereRaw(
					'LOWER("nombre") = LOWER(?)',
					[$nombre]
				)
				->exists();

			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'El detalle ya se encuentra registrado para este tipo.',
					'errors' => [
						'nombre' => [
							'Ya existe un detalle con este nombre dentro del tipo seleccionado.'
						],
					],
				], 422);
			}

			$detalle = new Detalle();

			$detalle->idTipo = $idTipo;
			$detalle->nombre = $nombre;

			$detalle->descripcion = $request->filled('descripcion')
				? trim($request->input('descripcion'))
				: null;

			$detalle->fechaRegistro = $request->input(
				'fechaRegistro',
				now()
			);

			$detalle->fechaBaja = null;

			$detalle->activo = $request->has('activo')
				? $request->boolean('activo')
				: true;

			$detalle->save();

			$detalle->load([
				'tipo:idTipo,nombre,activo',
			]);

			return response()->json([
				'success' => true,
				'message' => 'Detalle registrado correctamente.',
				'data' => $detalle,
			], 201);
		});
	}

	public function show(int $idDetalle): JsonResponse
	{
		$detalle = Detalle::with([
			'tipo:idTipo,nombre,activo',
		])->find($idDetalle);

		if (!$detalle) {
			return response()->json([
				'success' => false,
				'message' => 'Detalle no encontrado.',
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' => 'Detalle obtenido correctamente.',
			'data' => $detalle,
		]);
	}

	public function update(
		UpdateDetalleRequest $request,
		int $idDetalle
	): JsonResponse {
		return DB::transaction(function () use (
			$request,
			$idDetalle
		) {

			$detalle = Detalle::find($idDetalle);

			if (!$detalle) {
				return response()->json([
					'success' => false,
					'message' => 'Detalle no encontrado.',
				], 404);
			}

			$idTipo = $request->integer('idTipo');

			$nombre = trim(
				$request->input('nombre')
			);

			/*
			* Verificamos que el nuevo tipo exista
			* y esté activo.
			*/
			$tipo = Tipo::find($idTipo);

			if (!$tipo) {
				return response()->json([
					'success' => false,
					'message' => 'El tipo no existe o se encuentra eliminado.',
					'errors' => [
						'idTipo' => [
							'El tipo indicado no está disponible.'
						],
					],
				], 422);
			}

			if (!$tipo->activo) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede asignar el detalle a un tipo inactivo.',
					'errors' => [
						'idTipo' => [
							'El tipo se encuentra inactivo.'
						],
					],
				], 422);
			}

			/*
			* CONTROL DE DUPLICIDAD EN UPDATE.
			*
			* Excluimos el registro actual.
			*
			* Esto permite:
			*
			* Detalle 1:
			* idTipo = 1
			* nombre = Masculino
			*
			* actualizarlo manteniendo:
			*
			* idTipo = 1
			* nombre = Masculino
			*
			* sin que se detecte a sí mismo como duplicado.
			*/
			$existe = Detalle::withTrashed()
				->where('idTipo', $idTipo)
				->whereRaw(
					'LOWER("nombre") = LOWER(?)',
					[$nombre]
				)
				->where(
					'idDetalle',
					'<>',
					$detalle->idDetalle
				)
				->exists();

			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'El detalle ya se encuentra registrado para este tipo.',
					'errors' => [
						'nombre' => [
							'Ya existe otro detalle con este nombre dentro del tipo seleccionado.'
						],
					],
				], 422);
			}

			$detalle->idTipo = $idTipo;
			$detalle->nombre = $nombre;

			if ($request->has('descripcion')) {
				$detalle->descripcion = $request->filled('descripcion')
					? trim($request->input('descripcion'))
					: null;
			}

			if ($request->filled('fechaRegistro')) {
				$detalle->fechaRegistro = $request->input(
					'fechaRegistro'
				);
			}

			if ($request->has('fechaBaja')) {
				$detalle->fechaBaja = $request->input(
					'fechaBaja'
				);
			}

			if ($request->has('activo')) {
				$detalle->activo = $request->boolean('activo');
			}

			$detalle->save();

			$detalle->load([
				'tipo:idTipo,nombre,activo',
			]);

			return response()->json([
				'success' => true,
				'message' => 'Detalle actualizado correctamente.',
				'data' => $detalle,
			]);
		});
	}

	public function destroy(int $idDetalle): JsonResponse
	{
		return DB::transaction(function () use ($idDetalle) {

			$detalle = Detalle::find($idDetalle);

			if (!$detalle) {
				return response()->json([
					'success' => false,
					'message' => 'Detalle no encontrado.',
				], 404);
			}

			$detalle->activo = false;
			$detalle->fechaBaja = now();

			$detalle->save();

			$detalle->delete();

			return response()->json([
				'success' => true,
				'message' => 'Detalle eliminado correctamente.',
			]);
		});
	}

	public function restore(int $idDetalle): JsonResponse
	{
		return DB::transaction(function () use ($idDetalle) {

			$detalle = Detalle::withTrashed()
				->find($idDetalle);

			if (!$detalle) {
				return response()->json([
					'success' => false,
					'message' => 'Detalle no encontrado.',
				], 404);
			}

			if (!$detalle->trashed()) {
				return response()->json([
					'success' => false,
					'message' => 'El detalle no está eliminado.',
				], 422);
			}

			/*
			* Verificamos que el tipo siga existiendo
			* y esté activo.
			*/
			$tipo = Tipo::find(
				$detalle->idTipo
			);

			if (!$tipo || !$tipo->activo) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar el detalle porque el tipo no está activo.',
				], 422);
			}

			/*
			* CONTROL DE DUPLICIDAD ANTES DE RESTAURAR.
			*/
			$existe = Detalle::withTrashed()
				->where('idTipo', $detalle->idTipo)
				->whereRaw(
					'LOWER("nombre") = LOWER(?)',
					[$detalle->nombre]
				)
				->where(
					'idDetalle',
					'<>',
					$detalle->idDetalle
				)
				->exists();

			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar el detalle porque ya existe otro registro con el mismo nombre dentro del tipo.',
				], 422);
			}

			$detalle->restore();

			$detalle->activo = true;
			$detalle->fechaBaja = null;

			$detalle->save();

			$detalle->load([
				'tipo:idTipo,nombre,activo',
			]);

			return response()->json([
				'success' => true,
				'message' => 'Detalle restaurado correctamente.',
				'data' => $detalle,
			]);
		});
	}
}
