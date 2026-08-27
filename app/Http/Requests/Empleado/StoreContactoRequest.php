<?php

namespace App\Http\Requests\Empleado;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactoRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'idPersona' => [
				'required',
				'integer',
				'exists:pgsql.empleado.persona,idPersona',
			],
			'celular' => [
				'nullable',
				'string',
				'max:20',
			],
			'telefono' => [
				'nullable',
				'string',
				'max:20',
			],
			'celularReferencia' => [
				'nullable',
				'string',
				'max:20',
			],
			'correo' => [
				'nullable',
				'email',
				'max:150',
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
			'idPersona.required' => 'La persona es obligatoria.',
			'idPersona.integer' => 'El identificador de la persona no es válido.',
			'idPersona.exists' => 'La persona indicada no existe.',
			'celular.string' => 'El celular debe ser texto.',
			'celular.max' => 'El celular no puede superar los 20 caracteres.',
			'telefono.string' => 'El teléfono debe ser texto.',
			'telefono.max' => 'El teléfono no puede superar los 20 caracteres.',
			'celularReferencia.string' => 'El celular de referencia debe ser texto.',
			'celularReferencia.max' => 'El celular de referencia no puede superar los 20 caracteres.',
			'correo.email' => 'El correo electrónico no es válido.',
			'correo.max' => 'El correo electrónico no puede superar los 150 caracteres.',
			'fechaRegistro.date' => 'La fecha de registro no es válida.',
			'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
		];
	}
}
