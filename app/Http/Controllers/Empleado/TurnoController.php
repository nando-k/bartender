<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use App\Http\Requests\Empleado\StoreTurnoRequest;
use App\Http\Requests\Empleado\UpdateTurnoRequest;
use App\Models\Empleado\Persona;
use App\Models\Empleado\Turno;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TurnoController extends Controller
{
	public function index(Request $request): JsonResponse
	{
		$query = Turno::query()
			->with([
				'persona:idPersona,numeroDocumento,complemento,paterno,materno,nombres,activo',
			])
			->orderBy('idTurno', 'desc');

		if ($request->filled('idPersona')) {
			$query->where(
				'idPersona',
				$request->integer('idPersona')
			);
		}

		if ($request->filled('dia')) {
			$dia = trim($request->input('dia'));

			$query->whereRaw(
				'LOWER("dia") = LOWER(?)',
				[$dia]
			);
		}

		if ($request->has('activo')) {
			$query->where(
				'activo',
				$request->boolean('activo')
			);
		}

		if ($request->filled('buscar')) {
			$buscar = trim($request->input('buscar'));

			$query->whereRaw(
				'LOWER("dia") LIKE LOWER(?)',
				["%{$buscar}%"]
			);
		}

		$turnos = $query->paginate(
			$request->integer('perPage', 15)
		);

		return response()->json([
			'success' => true,
			'message' => 'Turnos obtenidos correctamente.',
			'data' => $turnos,
		]);
	}

	public function store(StoreTurnoRequest $request): JsonResponse
	{
		return DB::transaction(function () use ($request) {

			$idPersona = $request->integer('idPersona');

			$dia = trim(
				mb_strtolower(
					$request->input('dia'),
					'UTF-8'
				)
			);

			$horaIngreso = $request->input('horaIngreso');
			$horaSalida = $request->input('horaSalida');

			/*
			* Verificamos que la persona exista y esté activa.
			*/
			$persona = Persona::find($idPersona);

			if (!$persona) {
				return response()->json([
					'success' => false,
					'message' => 'La persona no existe o se encuentra eliminada.',
					'errors' => [
						'idPersona' => [
							'La persona indicada no está disponible.'
						],
					],
				], 422);
			}

			if (!$persona->activo) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede registrar un turno para una persona inactiva.',
					'errors' => [
						'idPersona' => [
							'La persona se encuentra inactiva.'
						],
					],
				], 422);
			}

			/*
			* CONTROL DE DUPLICIDAD.
			*
			* Una persona no puede tener exactamente
			* el mismo turno dos veces.
			*
			* Se utiliza withTrashed() para que un registro
			* eliminado lógicamente también sea considerado.
			*/
			$existe = Turno::withTrashed()
				->where('idPersona', $idPersona)
				->whereRaw(
					'LOWER("dia") = LOWER(?)',
					[$dia]
				)
				->where(
					'horaIngreso',
					$horaIngreso
				)
				->where(
					'horaSalida',
					$horaSalida
				)
				->exists();

			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'El turno ya se encuentra registrado.',
					'errors' => [
						'dia' => [
							'La persona ya tiene registrado este turno.'
						],
					],
				], 422);
			}

			$turno = new Turno();

			$turno->idPersona = $idPersona;
			$turno->dia = $dia;
			$turno->horaIngreso = $horaIngreso;
			$turno->horaSalida = $horaSalida;

			$turno->fechaRegistro = $request->input(
				'fechaRegistro',
				now()
			);

			$turno->fechaBaja = null;

			$turno->activo = $request->has('activo')
				? $request->boolean('activo')
				: true;

			$turno->save();

			$turno->load([
				'persona:idPersona,numeroDocumento,complemento,paterno,materno,nombres,activo',
			]);

			return response()->json([
				'success' => true,
				'message' => 'Turno registrado correctamente.',
				'data' => $turno,
			], 201);
		});
	}

	public function show(int $idTurno): JsonResponse
	{
		$turno = Turno::with([
			'persona:idPersona,numeroDocumento,complemento,paterno,materno,nombres,activo',
		])->find($idTurno);

		if (!$turno) {
			return response()->json([
				'success' => false,
				'message' => 'Turno no encontrado.',
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' => 'Turno obtenido correctamente.',
			'data' => $turno,
		]);
	}

	public function update(
		UpdateTurnoRequest $request,
		int $idTurno
	): JsonResponse {
		return DB::transaction(function () use (
			$request,
			$idTurno
		) {

			$turno = Turno::find($idTurno);

			if (!$turno) {
				return response()->json([
					'success' => false,
					'message' => 'Turno no encontrado.',
				], 404);
			}

			$idPersona = $request->integer('idPersona');

			$dia = trim(
				mb_strtolower(
					$request->input('dia'),
					'UTF-8'
				)
			);

			$horaIngreso = $request->input('horaIngreso');
			$horaSalida = $request->input('horaSalida');

			/*
			* Verificamos que la persona exista y esté activa.
			*/
			$persona = Persona::find($idPersona);

			if (!$persona) {
				return response()->json([
					'success' => false,
					'message' => 'La persona no existe o se encuentra eliminada.',
					'errors' => [
						'idPersona' => [
							'La persona indicada no está disponible.'
						],
					],
				], 422);
			}

			if (!$persona->activo) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede asignar un turno a una persona inactiva.',
					'errors' => [
						'idPersona' => [
							'La persona se encuentra inactiva.'
						],
					],
				], 422);
			}

			/*
			* CONTROL DE DUPLICIDAD EN UPDATE.
			*
			* Excluimos el turno actual.
			*/
			$existe = Turno::withTrashed()
				->where('idPersona', $idPersona)
				->whereRaw(
					'LOWER("dia") = LOWER(?)',
					[$dia]
				)
				->where(
					'horaIngreso',
					$horaIngreso
				)
				->where(
					'horaSalida',
					$horaSalida
				)
				->where(
					'idTurno',
					'<>',
					$turno->idTurno
				)
				->exists();

			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'El turno ya se encuentra registrado.',
					'errors' => [
						'dia' => [
							'La persona ya tiene registrado este turno.'
						],
					],
				], 422);
			}

			$turno->idPersona = $idPersona;
			$turno->dia = $dia;
			$turno->horaIngreso = $horaIngreso;
			$turno->horaSalida = $horaSalida;

			if ($request->filled('fechaRegistro')) {
				$turno->fechaRegistro = $request->input(
					'fechaRegistro'
				);
			}

			if ($request->has('fechaBaja')) {
				$turno->fechaBaja = $request->input(
					'fechaBaja'
				);
			}

			if ($request->has('activo')) {
				$turno->activo = $request->boolean('activo');
			}

			$turno->save();

			$turno->load([
				'persona:idPersona,numeroDocumento,complemento,paterno,materno,nombres,activo',
			]);

			return response()->json([
				'success' => true,
				'message' => 'Turno actualizado correctamente.',
				'data' => $turno,
			]);
		});
	}

	public function destroy(int $idTurno): JsonResponse
	{
		return DB::transaction(function () use ($idTurno) {

			$turno = Turno::find($idTurno);

			if (!$turno) {
				return response()->json([
					'success' => false,
					'message' => 'Turno no encontrado.',
				], 404);
			}

			$turno->activo = false;
			$turno->fechaBaja = now();

			$turno->save();

			$turno->delete();

			return response()->json([
				'success' => true,
				'message' => 'Turno eliminado correctamente.',
			]);
		});
	}

	public function restore(int $idTurno): JsonResponse
	{
		return DB::transaction(function () use ($idTurno) {

			$turno = Turno::withTrashed()
				->find($idTurno);

			if (!$turno) {
				return response()->json([
					'success' => false,
					'message' => 'Turno no encontrado.',
				], 404);
			}

			if (!$turno->trashed()) {
				return response()->json([
					'success' => false,
					'message' => 'El turno no está eliminado.',
				], 422);
			}

			/*
			* Antes de restaurar comprobamos que el turno
			* siga sin generar duplicidad.
			*/
			$existe = Turno::withTrashed()
				->where('idPersona', $turno->idPersona)
				->whereRaw(
					'LOWER("dia") = LOWER(?)',
					[$turno->dia]
				)
				->where(
					'horaIngreso',
					$turno->horaIngreso
				)
				->where(
					'horaSalida',
					$turno->horaSalida
				)
				->where(
					'idTurno',
					'<>',
					$turno->idTurno
				)
				->exists();

			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar el turno porque ya existe otro registro con los mismos datos.',
				], 422);
			}

			/*
			* Verificamos que la persona siga activa.
			*/
			$persona = Persona::find(
				$turno->idPersona
			);

			if (!$persona || !$persona->activo) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar el turno porque la persona no está activa.',
				], 422);
			}

			$turno->restore();

			$turno->activo = true;
			$turno->fechaBaja = null;

			$turno->save();

			$turno->load([
				'persona:idPersona,numeroDocumento,complemento,paterno,materno,nombres,activo',
			]);

			return response()->json([
				'success' => true,
				'message' => 'Turno restaurado correctamente.',
				'data' => $turno,
			]);
		});
	}
}
