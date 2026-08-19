<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use App\Http\Requests\Empleado\StoreContactoRequest;
use App\Http\Requests\Empleado\UpdateContactoRequest;
use App\Models\Empleado\Contacto;
use App\Models\Empleado\Persona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContactoController extends Controller
{
	public function index(Request $request): JsonResponse
	{
		$query = Contacto::query()
			->with([
				'persona:idPersona,numeroDocumento,complemento,nombres,paterno,materno',
			])
			->orderBy('idContacto', 'desc');

		if ($request->filled('idPersona')) {
			$query->where(
				'idPersona',
				$request->integer('idPersona')
			);
		}

		if ($request->filled('celular')) {
			$query->where(
				'celular',
				'ILIKE',
				'%' . trim($request->input('celular')) . '%'
			);
		}

		if ($request->filled('telefono')) {
			$query->where(
				'telefono',
				'ILIKE',
				'%' . trim($request->input('telefono')) . '%'
			);
		}

		if ($request->filled('correo')) {
			$query->where(
				'correo',
				'ILIKE',
				'%' . trim($request->input('correo')) . '%'
			);
		}

		if ($request->has('activo')) {
			$query->where(
				'activo',
				$request->boolean('activo')
			);
		}

		$contactos = $query->paginate(
			$request->integer('perPage', 15)
		);

		return response()->json([
			'success' => true,
			'message' => 'Contactos obtenidos correctamente.',
			'data' => $contactos,
		]);
	}

	public function store(StoreContactoRequest $request): JsonResponse
	{
		return DB::transaction(function () use ($request) {

			$idPersona = $request->integer('idPersona');

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
							'La persona indicada no está disponible.',
						],
					],
				], 422);
			}

			if (!$persona->activo) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede registrar un contacto para una persona inactiva.',
					'errors' => [
						'idPersona' => [
							'La persona se encuentra inactiva.',
						],
					],
				], 422);
			}

			/*
			* Normalizamos los datos.
			*/
			$celular = $this->limpiarDato(
				$request->input('celular')
			);

			$telefono = $this->limpiarDato(
				$request->input('telefono')
			);

			$celularReferencia = $this->limpiarDato(
				$request->input('celularReferencia')
			);

			$correo = $this->limpiarCorreo(
				$request->input('correo')
			);

			/*
			* Debe existir al menos un medio de contacto.
			*/
			if (
				$celular === null &&
				$telefono === null &&
				$celularReferencia === null &&
				$correo === null
			) {
				return response()->json([
					'success' => false,
					'message' => 'Debe registrar al menos un dato de contacto.',
					'errors' => [
						'contacto' => [
							'Debe indicar celular, teléfono, celular de referencia o correo.',
						],
					],
				], 422);
			}

			/*
			* =====================================================
			* CONTROL DE DUPLICIDAD
			* =====================================================
			*
			* Se compara:
			*
			* idPersona
			* celular
			* telefono
			* celularReferencia
			* correo
			*
			* Los NULL se comparan correctamente utilizando
			* whereNull().
			*/

			$queryDuplicidad = Contacto::withTrashed()
				->where('idPersona', $idPersona);

			$this->aplicarComparacionNullable(
				$queryDuplicidad,
				'celular',
				$celular
			);

			$this->aplicarComparacionNullable(
				$queryDuplicidad,
				'telefono',
				$telefono
			);

			$this->aplicarComparacionNullable(
				$queryDuplicidad,
				'celularReferencia',
				$celularReferencia
			);

			$this->aplicarComparacionNullable(
				$queryDuplicidad,
				'correo',
				$correo,
				true
			);

			if ($queryDuplicidad->exists()) {
				return response()->json([
					'success' => false,
					'message' => 'El contacto ya se encuentra registrado para esta persona.',
					'errors' => [
						'contacto' => [
							'Ya existe un contacto con los mismos datos.',
						],
					],
				], 422);
			}

			$contacto = new Contacto();

			$contacto->idPersona = $idPersona;
			$contacto->celular = $celular;
			$contacto->telefono = $telefono;
			$contacto->celularReferencia = $celularReferencia;
			$contacto->correo = $correo;
			$contacto->fechaRegistro = $request->input(
				'fechaRegistro',
				now()
			);
			$contacto->fechaBaja = null;
			$contacto->activo = $request->has('activo')
				? $request->boolean('activo')
				: true;

			$contacto->save();

			$contacto->load([
				'persona:idPersona,numeroDocumento,complemento,nombres,paterno,materno',
			]);

			return response()->json([
				'success' => true,
				'message' => 'Contacto registrado correctamente.',
				'data' => $contacto,
			], 201);
		});
	}

	public function show(int $idContacto): JsonResponse
	{
		$contacto = Contacto::with([
			'persona:idPersona,numeroDocumento,complemento,nombres,paterno,materno',
		])->find($idContacto);

		if (!$contacto) {
			return response()->json([
				'success' => false,
				'message' => 'Contacto no encontrado.',
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' => 'Contacto obtenido correctamente.',
			'data' => $contacto,
		]);
	}

	public function update(
		UpdateContactoRequest $request,
		int $idContacto
	): JsonResponse {
		return DB::transaction(function () use (
			$request,
			$idContacto
		) {

			$contacto = Contacto::find($idContacto);

			if (!$contacto) {
				return response()->json([
					'success' => false,
					'message' => 'Contacto no encontrado.',
				], 404);
			}

			$idPersona = $request->integer('idPersona');

			/*
			* Verificamos persona.
			*/
			$persona = Persona::find($idPersona);

			if (!$persona) {
				return response()->json([
					'success' => false,
					'message' => 'La persona no existe o se encuentra eliminada.',
					'errors' => [
						'idPersona' => [
							'La persona indicada no está disponible.',
						],
					],
				], 422);
			}

			if (!$persona->activo) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede asignar un contacto a una persona inactiva.',
					'errors' => [
						'idPersona' => [
							'La persona se encuentra inactiva.',
						],
					],
				], 422);
			}

			/*
			* Normalizamos datos.
			*/
			$celular = $this->limpiarDato(
				$request->input('celular')
			);

			$telefono = $this->limpiarDato(
				$request->input('telefono')
			);

			$celularReferencia = $this->limpiarDato(
				$request->input('celularReferencia')
			);

			$correo = $this->limpiarCorreo(
				$request->input('correo')
			);

			/*
			* Debe existir al menos un medio de contacto.
			*/
			if (
				$celular === null &&
				$telefono === null &&
				$celularReferencia === null &&
				$correo === null
			) {
				return response()->json([
					'success' => false,
					'message' => 'Debe registrar al menos un dato de contacto.',
					'errors' => [
						'contacto' => [
							'Debe indicar celular, teléfono, celular de referencia o correo.',
						],
					],
				], 422);
			}

			/*
			* =====================================================
			* CONTROL DE DUPLICIDAD EN UPDATE
			* =====================================================
			*/

			$queryDuplicidad = Contacto::withTrashed()
				->where('idPersona', $idPersona)
				->where(
					'idContacto',
					'<>',
					$contacto->idContacto
				);

			$this->aplicarComparacionNullable(
				$queryDuplicidad,
				'celular',
				$celular
			);

			$this->aplicarComparacionNullable(
				$queryDuplicidad,
				'telefono',
				$telefono
			);

			$this->aplicarComparacionNullable(
				$queryDuplicidad,
				'celularReferencia',
				$celularReferencia
			);

			$this->aplicarComparacionNullable(
				$queryDuplicidad,
				'correo',
				$correo,
				true
			);

			if ($queryDuplicidad->exists()) {
				return response()->json([
					'success' => false,
					'message' => 'El contacto ya se encuentra registrado para esta persona.',
					'errors' => [
						'contacto' => [
							'Ya existe otro contacto con los mismos datos.',
						],
					],
				], 422);
			}

			$contacto->idPersona = $idPersona;
			$contacto->celular = $celular;
			$contacto->telefono = $telefono;
			$contacto->celularReferencia = $celularReferencia;
			$contacto->correo = $correo;

			if ($request->filled('fechaRegistro')) {
				$contacto->fechaRegistro = $request->input(
					'fechaRegistro'
				);
			}

			if ($request->has('fechaBaja')) {
				$contacto->fechaBaja = $request->input(
					'fechaBaja'
				);
			}

			if ($request->has('activo')) {
				$contacto->activo = $request->boolean('activo');
			}

			$contacto->save();

			$contacto->load([
				'persona:idPersona,numeroDocumento,complemento,nombres,paterno,materno',
			]);

			return response()->json([
				'success' => true,
				'message' => 'Contacto actualizado correctamente.',
				'data' => $contacto,
			]);
		});
	}

	public function destroy(int $idContacto): JsonResponse
	{
		return DB::transaction(function () use ($idContacto) {

			$contacto = Contacto::find($idContacto);

			if (!$contacto) {
				return response()->json([
					'success' => false,
					'message' => 'Contacto no encontrado.',
				], 404);
			}

			$contacto->activo = false;
			$contacto->fechaBaja = now();

			$contacto->save();

			$contacto->delete();

			return response()->json([
				'success' => true,
				'message' => 'Contacto eliminado correctamente.',
			]);
		});
	}

	public function restore(int $idContacto): JsonResponse
	{
		return DB::transaction(function () use ($idContacto) {

			$contacto = Contacto::withTrashed()
				->find($idContacto);

			if (!$contacto) {
				return response()->json([
					'success' => false,
					'message' => 'Contacto no encontrado.',
				], 404);
			}

			if (!$contacto->trashed()) {
				return response()->json([
					'success' => false,
					'message' => 'El contacto no está eliminado.',
				], 422);
			}

			/*
			* Verificamos que la persona continúe existiendo
			* y esté activa.
			*/
			$persona = Persona::find(
				$contacto->idPersona
			);

			if (!$persona) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar el contacto porque la persona no existe o fue eliminada.',
				], 422);
			}

			if (!$persona->activo) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar el contacto porque la persona está inactiva.',
				], 422);
			}

			/*
			* =====================================================
			* CONTROL DE DUPLICIDAD ANTES DE RESTAURAR
			* =====================================================
			*/

			$queryDuplicidad = Contacto::withTrashed()
				->where(
					'idPersona',
					$contacto->idPersona
				)
				->where(
					'idContacto',
					'<>',
					$contacto->idContacto
				);

			$this->aplicarComparacionNullable(
				$queryDuplicidad,
				'celular',
				$contacto->celular
			);

			$this->aplicarComparacionNullable(
				$queryDuplicidad,
				'telefono',
				$contacto->telefono
			);

			$this->aplicarComparacionNullable(
				$queryDuplicidad,
				'celularReferencia',
				$contacto->celularReferencia
			);

			$this->aplicarComparacionNullable(
				$queryDuplicidad,
				'correo',
				$contacto->correo,
				true
			);

			if ($queryDuplicidad->exists()) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar el contacto porque ya existe otro contacto con los mismos datos.',
				], 422);
			}

			$contacto->restore();

			$contacto->activo = true;
			$contacto->fechaBaja = null;

			$contacto->save();

			$contacto->load([
				'persona:idPersona,numeroDocumento,complemento,nombres,paterno,materno',
			]);

			return response()->json([
				'success' => true,
				'message' => 'Contacto restaurado correctamente.',
				'data' => $contacto,
			]);
		});
	}

	private function aplicarComparacionNullable(
		$query,
		string $campo,
		mixed $valor,
		bool $insensible = false
	): void {
		if ($valor === null) {
			$query->whereNull($campo);
			return;
		}

		if ($insensible) {
			$query->whereRaw(
				'LOWER("' . $campo . '") = LOWER(?)',
				[$valor]
			);

			return;
		}

		$query->where(
			$campo,
			$valor
		);
	}

	private function limpiarDato(?string $valor): ?string
	{
		if ($valor === null) {
			return null;
		}

		$valor = trim($valor);

		if ($valor === '') {
			return null;
		}

		/*
		* Eliminamos espacios internos innecesarios.
		*/
		$valor = preg_replace('/\s+/', '', $valor);

		return $valor;
	}

	private function limpiarCorreo(?string $valor): ?string
	{
		if ($valor === null) {
			return null;
		}

		$valor = trim($valor);

		if ($valor === '') {
			return null;
		}

		return mb_strtolower($valor, 'UTF-8');
	}
}
