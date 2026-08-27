<?php
namespace App\Http\Requests\Almacen;
use Illuminate\Foundation\Http\FormRequest;
class UpdateProveedorRequest extends FormRequest
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
			'representanteLegal' => [
				'nullable',
				'string',
				'max:300',
			],
			'nit' => [
				'nullable',
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
				'exists:pgsql.seguridad.usuario,idUsuario',
			],
			'fechaBaja' => [
				'nullable',
				'date',
			],
			'IdUsuarioBaja' => [
				'nullable',
				'integer',
				'exists:pgsql.seguridad.usuario,idUsuario',
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
			'razonSocial.required' => 'La razón social es obligatoria.',
			'razonSocial.string' => 'La razón social debe ser texto.',
			'razonSocial.max' => 'La razón social no puede superar los 300 caracteres.',
			'representanteLegal.string' => 'El representante legal debe ser texto.',
			'representanteLegal.max' => 'El representante legal no puede superar los 300 caracteres.',
			'nit.string' => 'El NIT debe ser texto.',
			'nit.max' => 'El NIT no puede superar los 50 caracteres.',
			'fechaRegistro.date' => 'La fecha de registro no es válida.',
			'idUsuarioRegistro.required' => 'El usuario de registro es obligatorio.',
			'idUsuarioRegistro.integer' => 'El identificador del usuario de registro no es válido.',
			'idUsuarioRegistro.exists' => 'El usuario de registro no existe.',
			'fechaBaja.date' => 'La fecha de baja no es válida.',
			'IdUsuarioBaja.integer' => 'El identificador del usuario de baja no es válido.',
			'IdUsuarioBaja.exists' => 'El usuario de baja no existe.',
			'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
		];
	}
}
