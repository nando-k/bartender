<?php
namespace App\Http\Requests\Almacen;
use Illuminate\Foundation\Http\FormRequest;
class UpdateProductoPresentacionRequest extends FormRequest
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
				'exists:almacen.producto,idProducto',
			],
			'nombre' => [
				'required',
				'string',
				'max:300',
			],
			'codigo' => [
				'required',
				'string',
				'max:50',
			],
			'cantidadBase' => [
				'required',
				'numeric',
				'gt:0',
			],
			'precio' => [
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
			'idProducto.required' => 'El producto es obligatorio.',
			'idProducto.integer' => 'El identificador del producto no es válido.',
			'idProducto.exists' => 'El producto indicado no existe.',
			'nombre.required' => 'El nombre de la presentación es obligatorio.',
			'nombre.string' => 'El nombre de la presentación debe ser texto.',
			'nombre.max' => 'El nombre de la presentación no puede superar los 300 caracteres.',
			'codigo.required' => 'El código de la presentación es obligatorio.',
			'codigo.string' => 'El código debe ser texto.',
			'codigo.max' => 'El código no puede superar los 50 caracteres.',
			'cantidadBase.required' => 'La cantidad base es obligatoria.',
			'cantidadBase.numeric' => 'La cantidad base debe ser numérica.',
			'cantidadBase.gt' => 'La cantidad base debe ser mayor que cero.',
			'precio.required' => 'El precio es obligatorio.',
			'precio.numeric' => 'El precio debe ser numérico.',
			'precio.gte' => 'El precio no puede ser negativo.',
			'fechaRegistro.date' => 'La fecha de registro no es válida.',
			'idUsuarioRegistro.required' => 'El usuario de registro es obligatorio.',
			'idUsuarioRegistro.integer' => 'El identificador del usuario de registro no es válido.',
			'idUsuarioRegistro.exists' => 'El usuario de registro no existe.',
			'fechaBaja.date' => 'La fecha de baja no es válida.',
			'IdUsuarioBaja.integer' => 'El identificador del usuario de baja no es válido.',
			'IdUsuarioBaja.exists' => 'El usuario de baja no existe.',
			'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
		];
	}
}
