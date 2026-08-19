<?php

namespace App\Http\Requests\Almacen;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMovimientoRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'idInventario' => [
				'required',
				'integer',
				'exists:almacen.inventario,idInventario',
			],

			'idTipo' => [
				'required',
				'integer',
				'exists:parametro.detalle,idDetalle',
			],

			'cantidad' => [
				'required',
				'numeric',
				'gt:0',
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
			'idInventario.required' =>
				'El inventario es obligatorio.',

			'idInventario.integer' =>
				'El identificador del inventario no es válido.',

			'idInventario.exists' =>
				'El inventario indicado no existe.',

			'idTipo.required' =>
				'El tipo de movimiento es obligatorio.',

			'idTipo.integer' =>
				'El identificador del tipo de movimiento no es válido.',

			'idTipo.exists' =>
				'El tipo de movimiento indicado no existe.',

			'cantidad.required' =>
				'La cantidad es obligatoria.',

			'cantidad.numeric' =>
				'La cantidad debe ser numérica.',

			'cantidad.gt' =>
				'La cantidad debe ser mayor que cero.',

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
