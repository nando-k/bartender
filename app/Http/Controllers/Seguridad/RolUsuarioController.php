<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\StoreRolUsuarioRequest;
use App\Http\Requests\Seguridad\UpdateRolUsuarioRequest;
use App\Models\Seguridad\Rol;
use App\Models\Seguridad\RolUsuario;
use App\Models\Seguridad\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RolUsuarioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = RolUsuario::query()
            ->with([
                'usuario:idUsuario,idPersona,cuenta,activo',
                'rol:idRol,nombre,activo',
            ])
            ->orderBy('idRolUsuario', 'desc');

        if ($request->filled('idUsuario')) {
            $query->where(
                'idUsuario',
                $request->integer('idUsuario')
            );
        }

        if ($request->filled('idRol')) {
            $query->where(
                'idRol',
                $request->integer('idRol')
            );
        }

        if ($request->has('activo')) {
            $query->where(
                'activo',
                $request->boolean('activo')
            );
        }

        $registros = $query->paginate(
            $request->integer('perPage', 15)
        );

        return response()->json([
            'success' => true,
            'message' => 'Asignaciones de roles obtenidas correctamente.',
            'data' => $registros,
        ]);
    }

    public function store(
        StoreRolUsuarioRequest $request
    ): JsonResponse {
        return DB::transaction(function () use ($request) {

            $idUsuario = $request->integer('idUsuario');
            $idRol = $request->integer('idRol');

            /*
             * Verificamos que el usuario exista y esté activo.
             */
            $usuario = Usuario::find($idUsuario);

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario no existe o se encuentra eliminado.',
                    'errors' => [
                        'idUsuario' => [
                            'El usuario indicado no está disponible.',
                        ],
                    ],
                ], 422);
            }

            if (!$usuario->activo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede asignar un rol a un usuario inactivo.',
                    'errors' => [
                        'idUsuario' => [
                            'El usuario se encuentra inactivo.',
                        ],
                    ],
                ], 422);
            }

            /*
             * Verificamos que el rol exista y esté activo.
             */
            $rol = Rol::find($idRol);

            if (!$rol) {
                return response()->json([
                    'success' => false,
                    'message' => 'El rol no existe o se encuentra eliminado.',
                    'errors' => [
                        'idRol' => [
                            'El rol indicado no está disponible.',
                        ],
                    ],
                ], 422);
            }

            if (!$rol->activo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede asignar un rol inactivo.',
                    'errors' => [
                        'idRol' => [
                            'El rol se encuentra inactivo.',
                        ],
                    ],
                ], 422);
            }

            /*
             * CONTROL DE DUPLICIDAD.
             *
             * Un usuario puede tener varios roles,
             * pero no puede tener el mismo rol más de una vez.
             */
            $existe = RolUsuario::withTrashed()
                ->where('idUsuario', $idUsuario)
                ->where('idRol', $idRol)
                ->exists();

            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario ya tiene asignado este rol.',
                    'errors' => [
                        'idRol' => [
                            'La asignación usuario-rol ya existe.',
                        ],
                    ],
                ], 422);
            }

            $rolUsuario = new RolUsuario();

            $rolUsuario->idUsuario = $idUsuario;
            $rolUsuario->idRol = $idRol;
            $rolUsuario->fechaRegistro = $request->input(
                'fechaRegistro',
                now()
            );
            $rolUsuario->fechaBaja = null;
            $rolUsuario->activo = $request->has('activo')
                ? $request->boolean('activo')
                : true;

            $rolUsuario->save();

            $rolUsuario->load([
                'usuario:idUsuario,idPersona,cuenta,activo',
                'rol:idRol,nombre,activo',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rol asignado al usuario correctamente.',
                'data' => $rolUsuario,
            ], 201);
        });
    }

    public function show(int $idRolUsuario): JsonResponse
    {
        $rolUsuario = RolUsuario::with([
            'usuario:idUsuario,idPersona,cuenta,activo',
            'rol:idRol,nombre,activo',
        ])->find($idRolUsuario);

        if (!$rolUsuario) {
            return response()->json([
                'success' => false,
                'message' => 'Asignación de rol no encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Asignación obtenida correctamente.',
            'data' => $rolUsuario,
        ]);
    }

    public function update(
        UpdateRolUsuarioRequest $request,
        int $idRolUsuario
    ): JsonResponse {
        return DB::transaction(function () use (
            $request,
            $idRolUsuario
        ) {

            $rolUsuario = RolUsuario::find($idRolUsuario);

            if (!$rolUsuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Asignación de rol no encontrada.',
                ], 404);
            }

            $idUsuario = $request->integer('idUsuario');
            $idRol = $request->integer('idRol');

            /*
             * Verificamos usuario.
             */
            $usuario = Usuario::find($idUsuario);

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario no existe o se encuentra eliminado.',
                    'errors' => [
                        'idUsuario' => [
                            'El usuario indicado no está disponible.',
                        ],
                    ],
                ], 422);
            }

            if (!$usuario->activo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede asignar un rol a un usuario inactivo.',
                    'errors' => [
                        'idUsuario' => [
                            'El usuario se encuentra inactivo.',
                        ],
                    ],
                ], 422);
            }

            /*
             * Verificamos rol.
             */
            $rol = Rol::find($idRol);

            if (!$rol) {
                return response()->json([
                    'success' => false,
                    'message' => 'El rol no existe o se encuentra eliminado.',
                    'errors' => [
                        'idRol' => [
                            'El rol indicado no está disponible.',
                        ],
                    ],
                ], 422);
            }

            if (!$rol->activo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede asignar un rol inactivo.',
                    'errors' => [
                        'idRol' => [
                            'El rol se encuentra inactivo.',
                        ],
                    ],
                ], 422);
            }

            /*
             * CONTROL DE DUPLICIDAD EN UPDATE.
             *
             * Se comprueba la combinación:
             *
             * idUsuario + idRol
             *
             * excluyendo el registro actual.
             */
            $existe = RolUsuario::withTrashed()
                ->where('idUsuario', $idUsuario)
                ->where('idRol', $idRol)
                ->where(
                    'idRolUsuario',
                    '<>',
                    $rolUsuario->idRolUsuario
                )
                ->exists();

            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario ya tiene asignado este rol.',
                    'errors' => [
                        'idRol' => [
                            'La asignación usuario-rol ya existe.',
                        ],
                    ],
                ], 422);
            }

            $rolUsuario->idUsuario = $idUsuario;
            $rolUsuario->idRol = $idRol;

            if ($request->filled('fechaRegistro')) {
                $rolUsuario->fechaRegistro = $request->input(
                    'fechaRegistro'
                );
            }

            if ($request->has('fechaBaja')) {
                $rolUsuario->fechaBaja = $request->input(
                    'fechaBaja'
                );
            }

            if ($request->has('activo')) {
                $rolUsuario->activo = $request->boolean('activo');
            }

            $rolUsuario->save();

            $rolUsuario->load([
                'usuario:idUsuario,idPersona,cuenta,activo',
                'rol:idRol,nombre,activo',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Asignación de rol actualizada correctamente.',
                'data' => $rolUsuario,
            ]);
        });
    }

    public function destroy(int $idRolUsuario): JsonResponse
    {
        return DB::transaction(function () use ($idRolUsuario) {

            $rolUsuario = RolUsuario::find($idRolUsuario);

            if (!$rolUsuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Asignación de rol no encontrada.',
                ], 404);
            }

            $rolUsuario->activo = false;
            $rolUsuario->fechaBaja = now();
            $rolUsuario->save();

            $rolUsuario->delete();

            return response()->json([
                'success' => true,
                'message' => 'Asignación de rol eliminada correctamente.',
            ]);
        });
    }

    public function restore(int $idRolUsuario): JsonResponse
    {
        return DB::transaction(function () use ($idRolUsuario) {

            $rolUsuario = RolUsuario::withTrashed()
                ->find($idRolUsuario);

            if (!$rolUsuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Asignación de rol no encontrada.',
                ], 404);
            }

            if (!$rolUsuario->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La asignación no está eliminada.',
                ], 422);
            }

            /*
             * CONTROL DE DUPLICIDAD ANTES DE RESTAURAR.
             *
             * Puede existir otra asignación activa o eliminada
             * con la misma combinación.
             */
            $existe = RolUsuario::withTrashed()
                ->where('idUsuario', $rolUsuario->idUsuario)
                ->where('idRol', $rolUsuario->idRol)
                ->where(
                    'idRolUsuario',
                    '<>',
                    $rolUsuario->idRolUsuario
                )
                ->exists();

            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede restaurar la asignación porque la combinación usuario-rol ya existe.',
                ], 422);
            }

            /*
             * Verificamos que el usuario continúe activo.
             */
            $usuario = Usuario::find(
                $rolUsuario->idUsuario
            );

            if (!$usuario || !$usuario->activo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede restaurar porque el usuario no está activo.',
                ], 422);
            }

            /*
             * Verificamos que el rol continúe activo.
             */
            $rol = Rol::find(
                $rolUsuario->idRol
            );

            if (!$rol || !$rol->activo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede restaurar porque el rol no está activo.',
                ], 422);
            }

            $rolUsuario->restore();

            $rolUsuario->activo = true;
            $rolUsuario->fechaBaja = null;

            $rolUsuario->save();

            $rolUsuario->load([
                'usuario:idUsuario,idPersona,cuenta,activo',
                'rol:idRol,nombre,activo',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Asignación de rol restaurada correctamente.',
                'data' => $rolUsuario,
            ]);
        });
    }
}
