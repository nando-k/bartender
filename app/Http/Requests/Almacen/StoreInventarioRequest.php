<?php

namespace App\Http\Requests\Almacen;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventarioRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'idProducto' => [
				'required',
				'integer',
				'exists:pgsql.almacen.producto,idProducto',
			],

			'precioUnitario' => [
				'required',
				'numeric',
				'gte:0',
			],

			'cantidad' => [
				'required',
				'numeric',
				'gte:0',
			],

			'precioTotal' => [
				'required',
				'numeric',
				'gte:0',
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
		];
	}

	public function messages(): array
	{
		return [
			'idProducto.required' =>
				'El producto es obligatorio.',

			'idProducto.integer' =>
				'El identificador del producto no es válido.',

			'idProducto.exists' =>
				'El producto indicado no existe.',

			'precioUnitario.required' =>
				'El precio unitario es obligatorio.',

			'precioUnitario.numeric' =>
				'El precio unitario debe ser numérico.',

			'precioUnitario.gte' =>
				'El precio unitario no puede ser negativo.',

			'cantidad.required' =>
				'La cantidad es obligatoria.',

			'cantidad.numeric' =>
				'La cantidad debe ser numérica.',

			'cantidad.gte' =>
				'La cantidad no puede ser negativa.',

			'precioTotal.required' =>
				'El precio total es obligatorio.',

			'precioTotal.numeric' =>
				'El precio total debe ser numérico.',

			'precioTotal.gte' =>
				'El precio total no puede ser negativo.',

			'fechaRegistro.date' =>
				'La fecha de registro no es válida.',

			'idUsuarioRegistro.required' =>
				'El usuario de registro es obligatorio.',

			'idUsuarioRegistro.integer' =>
				'El identificador del usuario de registro no es válido.',

			'idUsuarioRegistro.exists' =>
				'El usuario de registro no existe.',
		];
	}
}
