<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;

class StoreRolRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'nombre' => [
				'required',
				'string',
				'max:100',
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
			'nombre.required' => 'El nombre del rol es obligatorio.',
			'nombre.string' => 'El nombre del rol debe ser texto.',
			'nombre.max' => 'El nombre del rol no puede superar los 100 caracteres.',
			'fechaRegistro.date' => 'La fecha de registro no es válida.',
			'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
		];
	}
}
