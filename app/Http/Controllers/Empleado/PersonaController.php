<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use App\Http\Requests\Empleado\StorePersonaRequest;
use App\Http\Requests\Empleado\UpdatePersonaRequest;
use App\Models\Empleado\Persona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersonaController extends Controller
{
	public function index(Request $request): JsonResponse
	{
		$query = Persona::query()
			->with([
				'usuario:idUsuario,idPersona,cuenta,activo',
			])
			->withCount('contactos')
			->orderBy('idPersona', 'desc');

		if ($request->filled('buscar')) {
			$buscar = trim($request->input('buscar'));

			$query->where(function ($q) use ($buscar) {
				$q->where(
					'numeroDocumento',
					'ILIKE',
					"%{$buscar}%"
				)
				->orWhere(
					'nombres',
					'ILIKE',
					"%{$buscar}%"
				)
				->orWhere(
					'paterno',
					'ILIKE',
					"%{$buscar}%"
				)
				->orWhere(
					'materno',
					'ILIKE',
					"%{$buscar}%"
				);
			});
		}

		if ($request->filled('numeroDocumento')) {
			$query->where(
				'numeroDocumento',
				$request->input('numeroDocumento')
			);
		}

		if ($request->filled('sexo')) {
			$query->where(
				'sexo',
				$request->input('sexo')
			);
		}

		if ($request->has('activo')) {
			$query->where(
				'activo',
				$request->boolean('activo')
			);
		}

		$personas = $query->paginate(
			$request->integer('perPage', 15)
		);

		return response()->json([
			'success' => true,
			'message' => 'Personas obtenidas correctamente.',
			'data' => $personas,
		]);
	}

	public function store(StorePersonaRequest $request): JsonResponse
	{
		return DB::transaction(function () use ($request) {

			$numeroDocumento = trim(
				$request->input('numeroDocumento')
			);

			$complemento = $request->input('complemento');

			$complemento = $complemento !== null
				? strtoupper(trim($complemento))
				: null;

			if ($complemento === '') {
				$complemento = null;
			}

			/*
			* =====================================================
			* CONTROL DE DUPLICIDAD
			* =====================================================
			*
			* La identificación lógica de una persona es:
			*
			* numeroDocumento + complemento
			*
			* withTrashed() hace que un registro eliminado
			* lógicamente también sea considerado duplicado.
			*/

			$queryDuplicidad = Persona::withTrashed()
				->whereRaw(
					'LOWER("numeroDocumento") = LOWER(?)',
					[$numeroDocumento]
				);

			if ($complemento === null) {
				$queryDuplicidad->whereNull('complemento');
			} else {
				$queryDuplicidad->whereRaw(
					'LOWER("complemento") = LOWER(?)',
					[$complemento]
				);
			}

			$existe = $queryDuplicidad->exists();

			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'La persona ya se encuentra registrada.',
					'errors' => [
						'numeroDocumento' => [
							'Ya existe una persona con el mismo número de documento y complemento.',
						],
					],
				], 422);
			}

			$persona = new Persona();

			$persona->numeroDocumento = $numeroDocumento;
			$persona->complemento = $complemento;
			$persona->sexo = trim($request->input('sexo'));
			$persona->fechaNacimiento = $request->input(
				'fechaNacimiento'
			);
			$persona->paterno = $this->limpiarTexto(
				$request->input('paterno')
			);
			$persona->materno = $this->limpiarTexto(
				$request->input('materno')
			);
			$persona->nombres = $this->limpiarTexto(
				$request->input('nombres')
			);
			$persona->fechaRegistro = $request->input(
				'fechaRegistro',
				now()
			);
			$persona->fechaBaja = null;
			$persona->activo = $request->has('activo')
				? $request->boolean('activo')
				: true;

			$persona->save();

			$persona->load([
				'usuario:idUsuario,idPersona,cuenta,activo',
			]);

			return response()->json([
				'success' => true,
				'message' => 'Persona registrada correctamente.',
				'data' => $persona,
			], 201);
		});
	}

	public function show(int $idPersona): JsonResponse
	{
		$persona = Persona::with([
			'usuario:idUsuario,idPersona,cuenta,activo',
			'contactos',
		])->find($idPersona);

		if (!$persona) {
			return response()->json([
				'success' => false,
				'message' => 'Persona no encontrada.',
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' => 'Persona obtenida correctamente.',
			'data' => $persona,
		]);
	}

	public function update(
		UpdatePersonaRequest $request,
		int $idPersona
	): JsonResponse {
		return DB::transaction(function () use (
			$request,
			$idPersona
		) {

			$persona = Persona::find($idPersona);

			if (!$persona) {
				return response()->json([
					'success' => false,
					'message' => 'Persona no encontrada.',
				], 404);
			}

			$numeroDocumento = trim(
				$request->input('numeroDocumento')
			);

			$complemento = $request->input('complemento');

			$complemento = $complemento !== null
				? strtoupper(trim($complemento))
				: null;

			if ($complemento === '') {
				$complemento = null;
			}

			/*
			* =====================================================
			* CONTROL DE DUPLICIDAD EN UPDATE
			* =====================================================
			*/

			$queryDuplicidad = Persona::withTrashed()
				->whereRaw(
					'LOWER("numeroDocumento") = LOWER(?)',
					[$numeroDocumento]
				)
				->where(
					'idPersona',
					'<>',
					$persona->idPersona
				);

			if ($complemento === null) {
				$queryDuplicidad->whereNull('complemento');
			} else {
				$queryDuplicidad->whereRaw(
					'LOWER("complemento") = LOWER(?)',
					[$complemento]
				);
			}

			$existe = $queryDuplicidad->exists();

			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'La persona ya se encuentra registrada.',
					'errors' => [
						'numeroDocumento' => [
							'Ya existe otra persona con el mismo número de documento y complemento.',
						],
					],
				], 422);
			}

			$persona->numeroDocumento = $numeroDocumento;
			$persona->complemento = $complemento;
			$persona->sexo = trim($request->input('sexo'));
			$persona->fechaNacimiento = $request->input(
				'fechaNacimiento'
			);
			$persona->paterno = $this->limpiarTexto(
				$request->input('paterno')
			);
			$persona->materno = $this->limpiarTexto(
				$request->input('materno')
			);
			$persona->nombres = $this->limpiarTexto(
				$request->input('nombres')
			);

			if ($request->filled('fechaRegistro')) {
				$persona->fechaRegistro = $request->input(
					'fechaRegistro'
				);
			}

			if ($request->has('fechaBaja')) {
				$persona->fechaBaja = $request->input(
					'fechaBaja'
				);
			}

			if ($request->has('activo')) {
				$persona->activo = $request->boolean('activo');
			}

			$persona->save();

			$persona->load([
				'usuario:idUsuario,idPersona,cuenta,activo',
			]);

			return response()->json([
				'success' => true,
				'message' => 'Persona actualizada correctamente.',
				'data' => $persona,
			]);
		});
	}

	public function destroy(int $idPersona): JsonResponse
	{
		return DB::transaction(function () use ($idPersona) {

			$persona = Persona::find($idPersona);

			if (!$persona) {
				return response()->json([
					'success' => false,
					'message' => 'Persona no encontrada.',
				], 404);
			}

			/*
			* Primero realizamos la baja funcional.
			*/
			$persona->activo = false;
			$persona->fechaBaja = now();
			$persona->save();

			/*
			* Luego SoftDelete.
			*/
			$persona->delete();

			return response()->json([
				'success' => true,
				'message' => 'Persona eliminada correctamente.',
			]);
		});
	}

	public function restore(int $idPersona): JsonResponse
	{
		return DB::transaction(function () use ($idPersona) {

			$persona = Persona::withTrashed()
				->find($idPersona);

			if (!$persona) {
				return response()->json([
					'success' => false,
					'message' => 'Persona no encontrada.',
				], 404);
			}

			if (!$persona->trashed()) {
				return response()->json([
					'success' => false,
					'message' => 'La persona no está eliminada.',
				], 422);
			}

			/*
			* =====================================================
			* CONTROL DE DUPLICIDAD ANTES DE RESTAURAR
			* =====================================================
			*/

			$queryDuplicidad = Persona::withTrashed()
				->whereRaw(
					'LOWER("numeroDocumento") = LOWER(?)',
					[$persona->numeroDocumento]
				)
				->where(
					'idPersona',
					'<>',
					$persona->idPersona
				);

			if ($persona->complemento === null) {
				$queryDuplicidad->whereNull('complemento');
			} else {
				$queryDuplicidad->whereRaw(
					'LOWER("complemento") = LOWER(?)',
					[$persona->complemento]
				);
			}

			$existe = $queryDuplicidad->exists();

			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar la persona porque ya existe otra persona con el mismo número de documento y complemento.',
				], 422);
			}

			$persona->restore();

			$persona->activo = true;
			$persona->fechaBaja = null;

			$persona->save();

			return response()->json([
				'success' => true,
				'message' => 'Persona restaurada correctamente.',
				'data' => $persona,
			]);
		});
	}

	private function limpiarTexto(?string $texto): ?string
	{
		if ($texto === null) {
			return null;
		}

		$texto = trim($texto);

		if ($texto === '') {
			return null;
		}

		$texto = preg_replace('/\s+/', ' ', $texto);

		return mb_strtoupper($texto, 'UTF-8');
	}
}
