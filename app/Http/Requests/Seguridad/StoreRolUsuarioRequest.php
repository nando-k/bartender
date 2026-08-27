<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;

class StoreRolUsuarioRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'idUsuario' => [
				'required',
				'integer',
				'exists:pgsql.seguridad.usuario,idUsuario',
			],
			'idRol' => [
				'required',
				'integer',
				'exists:pgsql.seguridad.rol,idRol',
			],
			'fechaRegistro' => [
				'nullable',
				'date',
			],
			'activo' => [
				'nullable',
				'boolean',
			],
		];
	}

	public function messages(): array
	{
		return [
			'idUsuario.required' => 'El usuario es obligatorio.',
			'idUsuario.integer' => 'El identificador del usuario no es válido.',
			'idUsuario.exists' => 'El usuario indicado no existe.',
			'idRol.required' => 'El rol es obligatorio.',
			'idRol.integer' => 'El identificador del rol no es válido.',
			'idRol.exists' => 'El rol indicado no existe.',
			'fechaRegistro.date' => 'La fecha de registro no es válida.',
			'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
		];
	}
}
