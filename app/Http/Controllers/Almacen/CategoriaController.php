<?php
namespace App\Http\Controllers\Almacen;
use App\Http\Controllers\Controller;
use App\Http\Requests\Almacen\StoreCategoriaRequest;
use App\Http\Requests\Almacen\UpdateCategoriaRequest;
use App\Models\Almacen\Categoria;
use App\Models\Seguridad\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CategoriaController extends Controller
{
	public function index(Request $request): JsonResponse
	{
		$query = Categoria::query()
			->with([
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			])
			->orderBy('idCategoria', 'desc');
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
		if ($request->filled('descripcion')) {
			$descripcion = trim(
				$request->input('descripcion')
			);
			$query->whereRaw(
				'LOWER("descripcion") = LOWER(?)',
				[$descripcion]
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
		$categorias = $query->paginate(
			$request->integer('perPage', 15)
		);
		return response()->json([
			'success' => true,
			'message' => 'Categorías obtenidas correctamente.',
			'data' => $categorias,
		]);
	}
	public function store(
		StoreCategoriaRequest $request
	): JsonResponse {
		return DB::transaction(function () use ($request) {
			$descripcion = trim(
				$request->input('descripcion')
			);
			$codigo = trim(
				$request->input('codigo')
			);
			$idUsuarioRegistro = $request->integer(
				'idUsuarioRegistro'
			);
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
							'No se puede registrar una categoría con un usuario inactivo.'
						],
					],
				], 422);
			}
			/*
			* CONTROL DE DUPLICIDAD POR CÓDIGO.
			*/
			$existeCodigo = Categoria::withTrashed()
				->whereRaw(
					'LOWER("codigo") = LOWER(?)',
					[$codigo]
				)
				->exists();
			if ($existeCodigo) {
				return response()->json([
					'success' => false,
					'message' => 'El código de la categoría ya se encuentra registrado.',
					'errors' => [
						'codigo' => [
							'Ya existe una categoría con este código.'
						],
					],
				], 422);
			}
			/*
			* CONTROL DE DUPLICIDAD POR DESCRIPCIÓN.
			*/
			$existeDescripcion = Categoria::withTrashed()
				->whereRaw(
					'LOWER("descripcion") = LOWER(?)',
					[$descripcion]
				)
				->exists();
			if ($existeDescripcion) {
				return response()->json([
					'success' => false,
					'message' => 'La descripción de la categoría ya se encuentra registrada.',
					'errors' => [
						'descripcion' => [
							'Ya existe una categoría con esta descripción.'
						],
					],
				], 422);
			}
			$categoria = new Categoria();
			$categoria->descripcion = $descripcion;
			$categoria->codigo = $codigo;
			$categoria->fechaRegistro = $request->input(
				'fechaRegistro',
				now()
			);
			$categoria->idUsuarioRegistro = $idUsuarioRegistro;
			$categoria->fechaBaja = null;
			$categoria->IdUsuarioBaja = null;
			$categoria->activo = $request->has('activo')
				? $request->boolean('activo')
				: true;
			$categoria->save();
			$categoria->load([
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			]);
			return response()->json([
				'success' => true,
				'message' => 'Categoría registrada correctamente.',
				'data' => $categoria,
			], 201);
		});
	}
	public function show(
		int $idCategoria
	): JsonResponse {
		$categoria = Categoria::with([
			'usuarioRegistro:idUsuario,cuenta,activo',
			'usuarioBaja:idUsuario,cuenta,activo',
		])->find($idCategoria);
		if (!$categoria) {
			return response()->json([
				'success' => false,
				'message' => 'Categoría no encontrada.',
			], 404);
		}
		return response()->json([
			'success' => true,
			'message' => 'Categoría obtenida correctamente.',
			'data' => $categoria,
		]);
	}
	public function update(
		UpdateCategoriaRequest $request,
		int $idCategoria
	): JsonResponse {
		return DB::transaction(function () use (
			$request,
			$idCategoria
		) {
			$categoria = Categoria::find(
				$idCategoria
			);
			if (!$categoria) {
				return response()->json([
					'success' => false,
					'message' => 'Categoría no encontrada.',
				], 404);
			}
			$descripcion = trim(
				$request->input('descripcion')
			);
			$codigo = trim(
				$request->input('codigo')
			);
			$idUsuarioRegistro = $request->integer(
				'idUsuarioRegistro'
			);
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
							'No se puede actualizar la categoría con un usuario inactivo.'
						],
					],
				], 422);
			}
			/*
			* CONTROL DE DUPLICIDAD POR CÓDIGO.
			* Excluimos la categoría actual.
			*/
			$existeCodigo = Categoria::withTrashed()
				->whereRaw(
					'LOWER("codigo") = LOWER(?)',
					[$codigo]
				)
				->where(
					'idCategoria',
					'<>',
					$categoria->idCategoria
				)
				->exists();
			if ($existeCodigo) {
				return response()->json([
					'success' => false,
					'message' => 'El código de la categoría ya se encuentra registrado.',
					'errors' => [
						'codigo' => [
							'Ya existe otra categoría con este código.'
						],
					],
				], 422);
			}
			/*
			* CONTROL DE DUPLICIDAD POR DESCRIPCIÓN.
			*/
			$existeDescripcion = Categoria::withTrashed()
				->whereRaw(
					'LOWER("descripcion") = LOWER(?)',
					[$descripcion]
				)
				->where(
					'idCategoria',
					'<>',
					$categoria->idCategoria
				)
				->exists();
			if ($existeDescripcion) {
				return response()->json([
					'success' => false,
					'message' => 'La descripción de la categoría ya se encuentra registrada.',
					'errors' => [
						'descripcion' => [
							'Ya existe otra categoría con esta descripción.'
						],
					],
				], 422);
			}
			$categoria->descripcion = $descripcion;
			$categoria->codigo = $codigo;
			if ($request->filled('fechaRegistro')) {
				$categoria->fechaRegistro = $request->input(
					'fechaRegistro'
				);
			}
			$categoria->idUsuarioRegistro = $idUsuarioRegistro;
			if ($request->has('fechaBaja')) {
				$categoria->fechaBaja = $request->input(
					'fechaBaja'
				);
			}
			if ($request->has('IdUsuarioBaja')) {
				$categoria->IdUsuarioBaja = $request->input(
					'IdUsuarioBaja'
				);
			}
			if ($request->has('activo')) {
				$categoria->activo = $request->boolean(
					'activo'
				);
			}
			$categoria->save();
			$categoria->load([
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			]);
			return response()->json([
				'success' => true,
				'message' => 'Categoría actualizada correctamente.',
				'data' => $categoria,
			]);
		});
	}
	public function destroy(
		int $idCategoria
	): JsonResponse {
		return DB::transaction(function () use ($idCategoria) {
			$categoria = Categoria::find(
				$idCategoria
			);
			if (!$categoria) {
				return response()->json([
					'success' => false,
					'message' => 'Categoría no encontrada.',
				], 404);
			}
			$categoria->activo = false;
			$categoria->fechaBaja = now();
			$categoria->save();
			$categoria->delete();
			return response()->json([
				'success' => true,
				'message' => 'Categoría eliminada correctamente.',
			]);
		});
	}
	public function restore(
		int $idCategoria
	): JsonResponse {
		return DB::transaction(function () use ($idCategoria) {
			$categoria = Categoria::withTrashed()
				->find($idCategoria);
			if (!$categoria) {
				return response()->json([
					'success' => false,
					'message' => 'Categoría no encontrada.',
				], 404);
			}
			if (!$categoria->trashed()) {
				return response()->json([
					'success' => false,
					'message' => 'La categoría no está eliminada.',
				], 422);
			}
			/*
			* CONTROL DE DUPLICIDAD POR CÓDIGO.
			*/
			$existeCodigo = Categoria::withTrashed()
				->whereRaw(
					'LOWER("codigo") = LOWER(?)',
					[$categoria->codigo]
				)
				->where(
					'idCategoria',
					'<>',
					$categoria->idCategoria
				)
				->exists();
			if ($existeCodigo) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar la categoría porque ya existe otra con el mismo código.',
				], 422);
			}
			/*
			* CONTROL DE DUPLICIDAD POR DESCRIPCIÓN.
			*/
			$existeDescripcion = Categoria::withTrashed()
				->whereRaw(
					'LOWER("descripcion") = LOWER(?)',
					[$categoria->descripcion]
				)
				->where(
					'idCategoria',
					'<>',
					$categoria->idCategoria
				)
				->exists();
			if ($existeDescripcion) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar la categoría porque ya existe otra con la misma descripción.',
				], 422);
			}
			/*
			* Verificamos que el usuario de registro
			* siga existiendo y esté activo.
			*/
			$usuarioRegistro = Usuario::find(
				$categoria->idUsuarioRegistro
			);
			if (!$usuarioRegistro || !$usuarioRegistro->activo) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar la categoría porque el usuario de registro no está activo.',
				], 422);
			}
			$categoria->restore();
			$categoria->activo = true;
			$categoria->fechaBaja = null;
			$categoria->IdUsuarioBaja = null;
			$categoria->save();
			$categoria->load([
				'usuarioRegistro:idUsuario,cuenta,activo',
				'usuarioBaja:idUsuario,cuenta,activo',
			]);
			return response()->json([
				'success' => true,
				'message' => 'Categoría restaurada correctamente.',
				'data' => $categoria,
			]);
		});
	}
}
