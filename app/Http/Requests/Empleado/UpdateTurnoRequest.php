<?php

namespace App\Http\Requests\Empleado;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTurnoRequest extends FormRequest
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
			'dia' => [
				'required',
				'string',
				'max:50',
			],
			'horaIngreso' => [
				'required',
				'date',
			],
			'horaSalida' => [
				'required',
				'date',
				'after:horaIngreso',
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
			'idPersona.required' => 'La persona es obligatoria.',
			'idPersona.integer' => 'El identificador de la persona no es válido.',
			'idPersona.exists' => 'La persona indicada no existe.',
			'dia.required' => 'El día es obligatorio.',
			'dia.string' => 'El día debe ser texto.',
			'dia.max' => 'El día no puede superar los 50 caracteres.',
			'horaIngreso.required' => 'La hora de ingreso es obligatoria.',
			'horaIngreso.date' => 'La hora de ingreso no es válida.',
			'horaSalida.required' => 'La hora de salida es obligatoria.',
			'horaSalida.date' => 'La hora de salida no es válida.',
			'horaSalida.after' => 'La hora de salida debe ser posterior a la hora de ingreso.',
			'fechaRegistro.date' => 'La fecha de registro no es válida.',
			'fechaBaja.date' => 'La fecha de baja no es válida.',
			'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
		];
	}
}
