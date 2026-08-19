<?php

namespace App\Http\Requests\Almacen;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClienteRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'razonSocial' => [
				'required',
				'string',
				'max:300',
			],

			'numeroDocumento' => [
				'required',
				'string',
				'max:50',
			],

			'fechaRegistro' => [
				'nullable',
				'date',
			],

			'idUsuarioRegistro' => [
				'required',
				'integer',
				'exists:seguridad.usuario,idUsuario',
			],

			'fechaBaja' => [
				'nullable',
				'date',
			],

			'IdUsuarioBaja' => [
				'nullable',
				'integer',
				'exists:seguridad.usuario,idUsuario',
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
			'razonSocial.required' =>
				'La razón social es obligatoria.',

			'razonSocial.string' =>
				'La razón social debe ser texto.',

			'razonSocial.max' =>
				'La razón social no puede superar los 300 caracteres.',

			'numeroDocumento.required' =>
				'El número de documento/NIT es obligatorio.',

			'numeroDocumento.string' =>
				'El número de documento/NIT debe ser texto.',

			'numeroDocumento.max' =>
				'El número de documento/NIT no puede superar los 50 caracteres.',

			'fechaRegistro.date' =>
				'La fecha de registro no es válida.',

			'idUsuarioRegistro.required' =>
				'El usuario de registro es obligatorio.',

			'idUsuarioRegistro.integer' =>
				'El identificador del usuario de registro no es válido.',

			'idUsuarioRegistro.exists' =>
				'El usuario de registro no existe.',

			'fechaBaja.date' =>
				'La fecha de baja no es válida.',

			'IdUsuarioBaja.integer' =>
				'El identificador del usuario de baja no es válido.',

			'IdUsuarioBaja.exists' =>
				'El usuario de baja no existe.',

			'activo.boolean' =>
				'El campo activo debe ser verdadero o falso.',
		];
	}
}
