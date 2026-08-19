<?php

namespace App\Http\Requests\Empleado;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePersonaRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'numeroDocumento' => [
				'required',
				'string',
				'max:50',
			],
			'complemento' => [
				'nullable',
				'string',
				'max:2',
			],
			'sexo' => [
				'required',
				'string',
				'max:255',
			],
			'fechaNacimiento' => [
				'required',
				'date',
			],
			'paterno' => [
				'nullable',
				'string',
				'max:80',
			],
			'materno' => [
				'nullable',
				'string',
				'max:80',
			],
			'nombres' => [
				'required',
				'string',
				'max:150',
			],
			'fechaRegistro' => [
				'nullable',
				'date',
			],
			'fechaBaja' => [
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
			'numeroDocumento.required' => 'El número de documento es obligatorio.',
			'numeroDocumento.string' => 'El número de documento debe ser texto.',
			'numeroDocumento.max' => 'El número de documento no puede superar los 50 caracteres.',
			'complemento.string' => 'El complemento debe ser texto.',
			'complemento.max' => 'El complemento no puede superar los 2 caracteres.',
			'sexo.required' => 'El sexo es obligatorio.',
			'sexo.string' => 'El sexo debe ser texto.',
			'sexo.max' => 'El sexo no puede superar los 255 caracteres.',
			'fechaNacimiento.required' => 'La fecha de nacimiento es obligatoria.',
			'fechaNacimiento.date' => 'La fecha de nacimiento no es válida.',
			'paterno.string' => 'El apellido paterno debe ser texto.',
			'paterno.max' => 'El apellido paterno no puede superar los 80 caracteres.',
			'materno.string' => 'El apellido materno debe ser texto.',
			'materno.max' => 'El apellido materno no puede superar los 80 caracteres.',
			'nombres.required' => 'El nombre es obligatorio.',
			'nombres.string' => 'El nombre debe ser texto.',
			'nombres.max' => 'El nombre no puede superar los 150 caracteres.',
			'fechaRegistro.date' => 'La fecha de registro no es válida.',
			'fechaBaja.date' => 'La fecha de baja no es válida.',
			'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
		];
	}
}
