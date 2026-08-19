<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\StoreUsuarioRequest;
use App\Http\Requests\Seguridad\UpdateUsuarioRequest;
use App\Models\Seguridad\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
	public function index(Request $request): JsonResponse
	{
		$query = Usuario::query()
			->with('persona')
			->orderBy('idUsuario', 'desc');

		if ($request->filled('buscar')) {
			$buscar = trim($request->input('buscar'));

			$query->where(function ($q) use ($buscar) {
				$q->where('cuenta', 'ILIKE', "%{$buscar}%");
			});
		}

		if ($request->has('activo')) {
			$query->where(
				'activo',
				filter_var(
					$request->input('activo'),
					FILTER_VALIDATE_BOOLEAN
				)
			);
		}

		$usuarios = $query->paginate(
			$request->integer('perPage', 15)
		);

		return response()->json([
			'success' => true,
			'message' => 'Usuarios obtenidos correctamente.',
			'data' => $usuarios,
		]);
	}

	public function store(StoreUsuarioRequest $request): JsonResponse
	{
		return DB::transaction(function () use ($request) {
			$cuenta = trim($request->input('cuenta'));

			/*
			* Control explícito de duplicidad.
			*
			* withTrashed() es importante porque tampoco queremos
			* crear otro usuario con una cuenta que pertenece a
			* un registro eliminado lógicamente.
			*/
			$existe = Usuario::withTrashed()
				->whereRaw('LOWER("cuenta") = LOWER(?)', [$cuenta])
				->exists();

			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'La cuenta ya se encuentra registrada.',
					'errors' => [
						'cuenta' => [
							'La cuenta ya existe.'
						]
					],
				], 422);
			}

			/*
			* Verificamos también que la persona no tenga
			* otro usuario activo/eliminado.
			*/
			$usuarioPersona = Usuario::withTrashed()
				->where('idPersona', $request->integer('idPersona'))
				->exists();

			if ($usuarioPersona) {
				return response()->json([
					'success' => false,
					'message' => 'La persona ya tiene un usuario registrado.',
					'errors' => [
						'idPersona' => [
							'La persona ya tiene un usuario asociado.'
						]
					],
				], 422);
			}

			$usuario = new Usuario();

			$usuario->idPersona = $request->integer('idPersona');
			$usuario->cuenta = $cuenta;

			/*
			* Aunque el campo se llama passwordHash,
			* recibimos la contraseña y la almacenamos
			* correctamente hasheada.
			*/
			$usuario->passwordHash = Hash::make(
				$request->input('passwordHash')
			);

			$usuario->fechaRegistro = $request->input(
				'fechaRegistro',
				now()
			);

			$usuario->activo = $request->has('activo')
				? $request->boolean('activo')
				: true;

			$usuario->save();

			$usuario->load('persona');

			return response()->json([
				'success' => true,
				'message' => 'Usuario registrado correctamente.',
				'data' => $usuario,
			], 201);
		});
	}

	public function show(int $idUsuario): JsonResponse
	{
		$usuario = Usuario::with('persona')
			->find($idUsuario);

		if (!$usuario) {
			return response()->json([
				'success' => false,
				'message' => 'Usuario no encontrado.',
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' => 'Usuario obtenido correctamente.',
			'data' => $usuario,
		]);
	}

	public function update(
		UpdateUsuarioRequest $request,
		int $idUsuario
	): JsonResponse {
		return DB::transaction(function () use (
			$request,
			$idUsuario
		) {
			$usuario = Usuario::find($idUsuario);

			if (!$usuario) {
				return response()->json([
					'success' => false,
					'message' => 'Usuario no encontrado.',
				], 404);
			}

			$cuenta = trim($request->input('cuenta'));

			/*
			* Control de duplicidad excluyendo
			* el usuario actual.
			*/
			$existe = Usuario::withTrashed()
				->whereRaw('LOWER("cuenta") = LOWER(?)', [$cuenta])
				->where('idUsuario', '<>', $usuario->idUsuario)
				->exists();

			if ($existe) {
				return response()->json([
					'success' => false,
					'message' => 'La cuenta ya se encuentra registrada.',
					'errors' => [
						'cuenta' => [
							'La cuenta ya existe.'
						]
					],
				], 422);
			}

			/*
			* También controlamos que la persona no
			* pertenezca a otro usuario.
			*/
			$usuarioPersona = Usuario::withTrashed()
				->where('idPersona', $request->integer('idPersona'))
				->where('idUsuario', '<>', $usuario->idUsuario)
				->exists();

			if ($usuarioPersona) {
				return response()->json([
					'success' => false,
					'message' => 'La persona ya tiene otro usuario registrado.',
					'errors' => [
						'idPersona' => [
							'La persona ya tiene otro usuario asociado.'
						]
					],
				], 422);
			}

			$usuario->idPersona = $request->integer('idPersona');
			$usuario->cuenta = $cuenta;

			/*
			* La contraseña solamente se cambia si
			* fue enviada.
			*/
			if ($request->filled('passwordHash')) {
				$usuario->passwordHash = Hash::make(
					$request->input('passwordHash')
				);
			}

			if ($request->filled('fechaRegistro')) {
				$usuario->fechaRegistro = $request->input(
					'fechaRegistro'
				);
			}

			if ($request->has('fechaBaja')) {
				$usuario->fechaBaja = $request->input('fechaBaja');
			}

			if ($request->has('activo')) {
				$usuario->activo = $request->boolean('activo');
			}

			$usuario->save();

			$usuario->load('persona');

			return response()->json([
				'success' => true,
				'message' => 'Usuario actualizado correctamente.',
				'data' => $usuario,
			]);
		});
	}

	public function destroy(int $idUsuario): JsonResponse
	{
		return DB::transaction(function () use ($idUsuario) {
			$usuario = Usuario::find($idUsuario);

			if (!$usuario) {
				return response()->json([
					'success' => false,
					'message' => 'Usuario no encontrado.',
				], 404);
			}

			$usuario->activo = false;
			$usuario->fechaBaja = now();
			$usuario->save();

			$usuario->delete();

			return response()->json([
				'success' => true,
				'message' => 'Usuario eliminado correctamente.',
			]);
		});
	}

	public function restore(int $idUsuario): JsonResponse
	{
		return DB::transaction(function () use ($idUsuario) {
			$usuario = Usuario::withTrashed()
				->find($idUsuario);

			if (!$usuario) {
				return response()->json([
					'success' => false,
					'message' => 'Usuario no encontrado.',
				], 404);
			}

			if (!$usuario->trashed()) {
				return response()->json([
					'success' => false,
					'message' => 'El usuario no está eliminado.',
				], 422);
			}

			/*
			* Antes de restaurar comprobamos nuevamente
			* que no exista otra cuenta/persona ocupando
			* la misma información.
			*/
			$duplicadoCuenta = Usuario::withTrashed()
				->whereRaw(
					'LOWER("cuenta") = LOWER(?)',
					[$usuario->cuenta]
				)
				->where('idUsuario', '<>', $usuario->idUsuario)
				->exists();

			if ($duplicadoCuenta) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar el usuario porque la cuenta ya está siendo utilizada.',
				], 422);
			}

			$duplicadoPersona = Usuario::withTrashed()
				->where('idPersona', $usuario->idPersona)
				->where('idUsuario', '<>', $usuario->idUsuario)
				->exists();

			if ($duplicadoPersona) {
				return response()->json([
					'success' => false,
					'message' => 'No se puede restaurar el usuario porque la persona ya tiene otro usuario.',
				], 422);
			}

			$usuario->restore();

			$usuario->activo = true;
			$usuario->fechaBaja = null;
			$usuario->save();

			return response()->json([
				'success' => true,
				'message' => 'Usuario restaurado correctamente.',
				'data' => $usuario,
			]);
		});
	}
}
