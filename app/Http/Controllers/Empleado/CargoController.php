<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use App\Http\Requests\Empleado\StoreCargoRequest;
use App\Http\Requests\Empleado\UpdateCargoRequest;
use App\Models\Empleado\Cargo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CargoController extends Controller
{
	public function index(Request $request): JsonResponse
	{
		$query = Cargo::query()
			->orderBy('nombre');

		if ($request->filled('buscar')) {
			$buscar = trim($request->input('buscar'));

			$query->where(function ($query) use ($buscar) {
				$query->where(
					'nombre',
					'ILIKE',
					"%{$buscar}%"
				)
				->orWhere(
					'descripcion',
					'ILIKE',
					"%{$buscar}%"
				);
			});
		}

		if ($request->has('activo')) {
			$query->where(
				'activo',
				$request->boolean('activo')
			);
		}

		$cargos = $query->paginate(
			$request->integer('perPage', 15)
		);

		return response()->json([
			'success' => true,
			'message' => 'Cargos obtenidos correctamente.',
			'data' => $cargos,
		]);
	}

	public function store(
		StoreCargoRequest $request
	): JsonResponse {
		return DB::transaction(function () use ($request) {

			$nombre = $this->limpiarTexto(
				$request->input('nombre')
			);

			/*
			* ==========================================
			* CONTROL DE DUPLICIDAD
			* ==========================================
			*
			* Se considera duplicado incluso un registro
			* eliminado mediante SoftDelete.
			*/

			$existe = Cargo::withTrashed()
				->whereRaw(
					'LOWER("nombre") = LOWER(?)',
					[$nombre]
				)
				->exists();

			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'El cargo ya se encuentra registrado.',
					'errors' => [
						'nombre' => [
							'Ya existe un cargo con el mismo nombre.',
						],
					],
				], 422);
			}

			$cargo = new Cargo();

			$cargo->nombre = $nombre;
			$cargo->descripcion = $this->limpiarDescripcion(
				$request->input('descripcion')
			);
			$cargo->fechaRegistro = $request->input(
				'fechaRegistro',
				now()
			);
			$cargo->fechaBaja = null;
			$cargo->activo = $request->has('activo')
				? $request->boolean('activo')
				: true;

			$cargo->save();

			return response()->json([
				'success' => true,
				'message' => 'Cargo registrado correctamente.',
				'data' => $cargo,
			], 201);
		});
	}

	public function show(
		int $idCargo
	): JsonResponse {
		$cargo = Cargo::find($idCargo);

		if (!$cargo) {
			return response()->json([
				'success' => false,
				'message' => 'Cargo no encontrado.',
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' => 'Cargo obtenido correctamente.',
			'data' => $cargo,
		]);
	}

	public function update(
		UpdateCargoRequest $request,
		int $idCargo
	): JsonResponse {
		return DB::transaction(function () use (
			$request,
			$idCargo
		) {

			$cargo = Cargo::find($idCargo);

			if (!$cargo) {
				return response()->json([
					'success' => false,
					'message' => 'Cargo no encontrado.',
				], 404);
			}

			$nombre = $this->limpiarTexto(
				$request->input('nombre')
			);

			/*
			* ==========================================
			* CONTROL DE DUPLICIDAD
			* ==========================================
			*/

			$existe = Cargo::withTrashed()
				->whereRaw(
					'LOWER("nombre") = LOWER(?)',
					[$nombre]
				)
				->where(
					'idCargo',
					'<>',
					$cargo->idCargo
				)
				->exists();

			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'El cargo ya se encuentra registrado.',
					'errors' => [
						'nombre' => [
							'Ya existe otro cargo con el mismo nombre.',
						],
					],
				], 422);
			}

			$cargo->nombre = $nombre;
			$cargo->descripcion = $this->limpiarDescripcion(
				$request->input('descripcion')
			);

			if ($request->filled('fechaRegistro')) {
				$cargo->fechaRegistro = $request->input(
					'fechaRegistro'
				);
			}

			if ($request->has('fechaBaja')) {
				$cargo->fechaBaja = $request->input(
					'fechaBaja'
				);
			}

			if ($request->has('activo')) {
				$cargo->activo = $request->boolean('activo');
			}

			$cargo->save();

			return response()->json([
				'success' => true,
				'message' => 'Cargo actualizado correctamente.',
				'data' => $cargo,
			]);
		});
	}

	public function destroy(
		int $idCargo
	): JsonResponse {
		return DB::transaction(function () use ($idCargo) {

			$cargo = Cargo::find($idCargo);

			if (!$cargo) {
				return response()->json([
					'success' => false,
					'message' => 'Cargo no encontrado.',
				], 404);
			}

			$cargo->activo = false;
			$cargo->fechaBaja = now();

			$cargo->save();

			$cargo->delete();

			return response()->json([
				'success' => true,
				'message' => 'Cargo eliminado correctamente.',
			]);
		});
	}

	public function restore(
		int $idCargo
	): JsonResponse {
		return DB::transaction(function () use ($idCargo) {

			$cargo = Cargo::withTrashed()
				->find($idCargo);

			if (!$cargo) {
				return response()->json([
					'success' => false,
					'message' => 'Cargo no encontrado.',
				], 404);
			}

			if (!$cargo->trashed()) {
				return response()->json([
					'success' => false,
					'message' => 'El cargo no está eliminado.',
				], 422);
			}

			/*
			* ==========================================
			* CONTROL DE DUPLICIDAD ANTES DE RESTAURAR
			* ==========================================
			*/

			$existe = Cargo::withTrashed()
				->whereRaw(
					'LOWER("nombre") = LOWER(?)',
					[$cargo->nombre]
				)
				->where(
					'idCargo',
					'<>',
					$cargo->idCargo
				)
				->exists();

			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar el cargo porque ya existe otro cargo con el mismo nombre.',
				], 422);
			}

			$cargo->restore();

			$cargo->activo = true;
			$cargo->fechaBaja = null;

			$cargo->save();

			return response()->json([
				'success' => true,
				'message' => 'Cargo restaurado correctamente.',
				'data' => $cargo,
			]);
		});
	}

	private function limpiarTexto(
		?string $texto
	): ?string {
		if ($texto === null) {
			return null;
		}

		$texto = trim($texto);

		if ($texto === '') {
			return null;
		}

		$texto = preg_replace(
			'/\s+/',
			' ',
			$texto
		);

		return mb_strtoupper(
			$texto,
			'UTF-8'
		);
	}

	private function limpiarDescripcion(
		?string $texto
	): ?string {
		if ($texto === null) {
			return null;
		}

		$texto = trim($texto);

		if ($texto === '') {
			return null;
		}

		return preg_replace(
			'/\s+/',
			' ',
			$texto
		);
	}
}
