<?php
namespace App\Http\Controllers\Almacen;
use App\Http\Controllers\Controller;
use App\Http\Requests\Almacen\StoreUnidadMedidaRequest;
use App\Http\Requests\Almacen\UpdateUnidadMedidaRequest;
use App\Models\Almacen\UnidadMedida;
use App\Models\Seguridad\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class UnidadMedidaController extends Controller
{
	public function index(Request $request): JsonResponse
	{
		$query = UnidadMedida::query()
			->with([
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			])
			->orderBy('idUnidadMedida', 'desc');
		if ($request->filled('buscar')) {
			$buscar = trim(
				$request->input('buscar')
			);
			$query->where(function ($q) use ($buscar) {
				$q->whereRaw(
					'LOWER("codigo") LIKE LOWER(?)',
					["%{$buscar}%"]
				)
				->orWhereRaw(
					'LOWER("descripcion") LIKE LOWER(?)',
					["%{$buscar}%"]
				);
			});
		}
		if ($request->filled('codigo')) {
			$codigo = trim(
				$request->input('codigo')
			);
			$query->whereRaw(
				'LOWER("codigo") = LOWER(?)',
				[$codigo]
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
		$unidadesMedida = $query->paginate(
			$request->integer('perPage', 15)
		);
		return response()->json([
			'success' => true,
			'message' => 'Unidades de medida obtenidas correctamente.',
			'data' => $unidadesMedida,
		]);
	}
	public function store(
		StoreUnidadMedidaRequest $request
	): JsonResponse {
		return DB::transaction(function () use ($request) {
			$codigo = trim(
				$request->input('codigo')
			);
			$descripcion = trim(
				$request->input('descripcion')
			);
			$factorBase = $request->input(
				'factorBase'
			);
			$idUsuarioRegistro = $request->integer(
				'idUsuarioRegistro'
			);
			/*
			* Verificamos el usuario de registro.
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
							'No se puede registrar una unidad de medida con un usuario inactivo.'
						],
					],
				], 422);
			}
			/*
			* CONTROL DE DUPLICIDAD.
			*
			* El código identifica la unidad de medida.
			*
			* Se utiliza withTrashed() para considerar también
			* los registros eliminados lógicamente.
			*/
			$existe = UnidadMedida::withTrashed()
				->whereRaw(
					'LOWER("codigo") = LOWER(?)',
					[$codigo]
				)
				->exists();
			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'El código de la unidad de medida ya se encuentra registrado.',
					'errors' => [
						'codigo' => [
							'Ya existe una unidad de medida con este código.'
						],
					],
				], 422);
			}
			$unidadMedida = new UnidadMedida();
			$unidadMedida->codigo = $codigo;
			$unidadMedida->descripcion = $descripcion;
			$unidadMedida->factorBase = $factorBase;
			$unidadMedida->fechaRegistro = $request->input(
				'fechaRegistro',
				now()
			);
			$unidadMedida->idUsuarioRegistro = $idUsuarioRegistro;
			$unidadMedida->fechaBaja = null;
			$unidadMedida->IdUsuarioBaja = null;
			$unidadMedida->activo = $request->has('activo')
				? $request->boolean('activo')
				: true;
			$unidadMedida->save();
			$unidadMedida->load([
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			]);
			return response()->json([
				'success' => true,
				'message' => 'Unidad de medida registrada correctamente.',
				'data' => $unidadMedida,
			], 201);
		});
	}
	public function show(
		int $idUnidadMedida
	): JsonResponse {
		$unidadMedida = UnidadMedida::with([
			'usuarioRegistro:idUsuario,cuenta,activo',
			'usuarioBaja:idUsuario,cuenta,activo',
		])->find($idUnidadMedida);
		if (!$unidadMedida) {
			return response()->json([
				'success' => false,
				'message' => 'Unidad de medida no encontrada.',
			], 404);
		}
		return response()->json([
			'success' => true,
			'message' => 'Unidad de medida obtenida correctamente.',
			'data' => $unidadMedida,
		]);
	}
	public function update(
		UpdateUnidadMedidaRequest $request,
		int $idUnidadMedida
	): JsonResponse {
		return DB::transaction(function () use (
			$request,
			$idUnidadMedida
		) {
			$unidadMedida = UnidadMedida::find(
				$idUnidadMedida
			);
			if (!$unidadMedida) {
				return response()->json([
					'success' => false,
					'message' => 'Unidad de medida no encontrada.',
				], 404);
			}
			$codigo = trim(
				$request->input('codigo')
			);
			$descripcion = trim(
				$request->input('descripcion')
			);
			$factorBase = $request->input(
				'factorBase'
			);
			$idUsuarioRegistro = $request->integer(
				'idUsuarioRegistro'
			);
			/*
			* Verificamos el usuario de registro.
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
							'No se puede actualizar la unidad de medida con un usuario inactivo.'
						],
					],
				], 422);
			}
			/*
			* CONTROL DE DUPLICIDAD EN UPDATE.
			*
			* Excluimos el registro actual.
			*/
			$existe = UnidadMedida::withTrashed()
				->whereRaw(
					'LOWER("codigo") = LOWER(?)',
					[$codigo]
				)
				->where(
					'idUnidadMedida',
					'<>',
					$unidadMedida->idUnidadMedida
				)
				->exists();
			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'El código de la unidad de medida ya se encuentra registrado.',
					'errors' => [
						'codigo' => [
							'Ya existe otra unidad de medida con este código.'
						],
					],
				], 422);
			}
			$unidadMedida->codigo = $codigo;
			$unidadMedida->descripcion = $descripcion;
			$unidadMedida->factorBase = $factorBase;
			if ($request->filled('fechaRegistro')) {
				$unidadMedida->fechaRegistro = $request->input(
					'fechaRegistro'
				);
			}
			$unidadMedida->idUsuarioRegistro = $idUsuarioRegistro;
			if ($request->has('fechaBaja')) {
				$unidadMedida->fechaBaja = $request->input(
					'fechaBaja'
				);
			}
			if ($request->has('IdUsuarioBaja')) {
				$unidadMedida->IdUsuarioBaja = $request->input(
					'IdUsuarioBaja'
				);
			}
			if ($request->has('activo')) {
				$unidadMedida->activo = $request->boolean(
					'activo'
				);
			}
			$unidadMedida->save();
			$unidadMedida->load([
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			]);
			return response()->json([
				'success' => true,
				'message' => 'Unidad de medida actualizada correctamente.',
				'data' => $unidadMedida,
			]);
		});
	}
	public function destroy(
		int $idUnidadMedida
	): JsonResponse {
		return DB::transaction(function () use ($idUnidadMedida) {
			$unidadMedida = UnidadMedida::find(
				$idUnidadMedida
			);
			if (!$unidadMedida) {
				return response()->json([
					'success' => false,
					'message' => 'Unidad de medida no encontrada.',
				], 404);
			}
			/*
			* Baja lógica.
			*/
			$unidadMedida->activo = false;
			$unidadMedida->fechaBaja = now();
			$unidadMedida->save();
			$unidadMedida->delete();
			return response()->json([
				'success' => true,
				'message' => 'Unidad de medida eliminada correctamente.',
			]);
		});
	}
	public function restore(
		int $idUnidadMedida
	): JsonResponse {
		return DB::transaction(function () use ($idUnidadMedida) {
			$unidadMedida = UnidadMedida::withTrashed()
				->find($idUnidadMedida);
			if (!$unidadMedida) {
				return response()->json([
					'success' => false,
					'message' => 'Unidad de medida no encontrada.',
				], 404);
			}
			if (!$unidadMedida->trashed()) {
				return response()->json([
					'success' => false,
					'message' => 'La unidad de medida no está eliminada.',
				], 422);
			}
			/*
			* CONTROL DE DUPLICIDAD ANTES DE RESTAURAR.
			*/
			$existe = UnidadMedida::withTrashed()
				->whereRaw(
					'LOWER("codigo") = LOWER(?)',
					[$unidadMedida->codigo]
				)
				->where(
					'idUnidadMedida',
					'<>',
					$unidadMedida->idUnidadMedida
				)
				->exists();
			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar la unidad de medida porque ya existe otra con el mismo código.',
				], 422);
			}
			/*
			* Verificamos que el usuario de registro
			* siga existiendo y esté activo.
			*/
			$usuarioRegistro = Usuario::find(
				$unidadMedida->idUsuarioRegistro
			);
			if (!$usuarioRegistro || !$usuarioRegistro->activo) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar la unidad de medida porque el usuario de registro no está activo.',
				], 422);
			}
			$unidadMedida->restore();
			$unidadMedida->activo = true;
			$unidadMedida->fechaBaja = null;
			$unidadMedida->IdUsuarioBaja = null;
			$unidadMedida->save();
			$unidadMedida->load([
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			]);
			return response()->json([
				'success' => true,
				'message' => 'Unidad de medida restaurada correctamente.',
				'data' => $unidadMedida,
			]);
		});
	}
}
