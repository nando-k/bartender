<?php

namespace App\Http\Requests\Parametro;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDetalleRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'idTipo' => [
				'required',
				'integer',
				'exists:parametro.tipo,idTipo',
			],
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
			'idTipo.required' => 'El tipo es obligatorio.',
			'idTipo.integer' => 'El identificador del tipo no es válido.',
			'idTipo.exists' => 'El tipo indicado no existe.',
			'nombre.required' => 'El nombre del detalle es obligatorio.',
			'nombre.string' => 'El nombre del detalle debe ser texto.',
			'nombre.max' => 'El nombre del detalle no puede superar los 100 caracteres.',
			'descripcion.string' => 'La descripción debe ser texto.',
			'fechaRegistro.date' => 'La fecha de registro no es válida.',
			'fechaBaja.date' => 'La fecha de baja no es válida.',
			'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
		];
	}
}
