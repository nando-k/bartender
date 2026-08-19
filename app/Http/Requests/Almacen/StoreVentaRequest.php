<?php

namespace App\Http\Requests\Almacen;

use Illuminate\Foundation\Http\FormRequest;

class StoreVentaRequest extends FormRequest
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

			'idCliente' => [
				'required',
				'integer',
				'exists:almacen.cliente,idCliente',
			],

			'idTipo' => [
				'required',
				'integer',
				'exists:parametro.detalle,idDetalle',
			],

			'total' => [
				'required',
				'numeric',
				'gte:0',
			],

			'idEstado' => [
				'required',
				'integer',
				'exists:parametro.detalle,idDetalle',
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

			'idCliente.required' =>
				'El cliente es obligatorio.',

			'idCliente.integer' =>
				'El identificador del cliente no es válido.',

			'idCliente.exists' =>
				'El cliente indicado no existe.',

			'idTipo.required' =>
				'El tipo de movimiento es obligatorio.',

			'idTipo.integer' =>
				'El identificador del tipo no es válido.',

			'idTipo.exists' =>
				'El tipo indicado no existe.',

			'total.required' =>
				'El total es obligatorio.',

			'total.numeric' =>
				'El total debe ser numérico.',

			'total.gte' =>
				'El total no puede ser negativo.',

			'idEstado.required' =>
				'El estado de la venta es obligatorio.',

			'idEstado.integer' =>
				'El identificador del estado no es válido.',

			'idEstado.exists' =>
				'El estado indicado no existe.',

			'fechaRegistro.date' =>
				'La fecha de registro no es válida.',

			'idUsuarioRegistro.required' =>
				'El usuario de registro es obligatorio.',

			'idUsuarioRegistro.integer' =>
				'El identificador del usuario no es válido.',

			'idUsuarioRegistro.exists' =>
				'El usuario de registro no existe.',
		];
	}
}
