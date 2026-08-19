<?php

namespace App\Http\Requests\Empleado;

use Illuminate\Foundation\Http\FormRequest;

class StoreCargoRequest extends FormRequest
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
				'max:150',
			],
			'descripcion' => [
				'nullable',
				'string',
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
			'nombre.required' => 'El nombre del cargo es obligatorio.',
			'nombre.string' => 'El nombre del cargo debe ser texto.',
			'nombre.max' => 'El nombre del cargo no puede superar los 150 caracteres.',
			'descripcion.string' => 'La descripción debe ser texto.',
			'fechaRegistro.date' => 'La fecha de registro no es válida.',
			'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
		];
	}
}
