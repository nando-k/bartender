<?php

namespace App\Http\Requests\Parametro;

use Illuminate\Foundation\Http\FormRequest;

class StoreTipoRequest extends FormRequest
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
			'nombre.required' => 'El nombre del tipo es obligatorio.',
			'nombre.string' => 'El nombre del tipo debe ser texto.',
			'nombre.max' => 'El nombre del tipo no puede superar los 100 caracteres.',
			'descripcion.string' => 'La descripción debe ser texto.',
			'fechaRegistro.date' => 'La fecha de registro no es válida.',
			'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
		];
	}
}
