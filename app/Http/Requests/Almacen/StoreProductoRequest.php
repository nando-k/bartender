<?php
namespace App\Http\Requests\Almacen;
use Illuminate\Foundation\Http\FormRequest;
class StoreProductoRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}
	public function rules(): array
	{
		return [
			'idUnidadMedida' => [
				'required',
				'integer',
				'exists:pgsql.almacen.unidadMedida,idUnidadMedida',
			],
			'descripcion' => [
				'required',
				'string',
				'max:300',
			],
			'codigo' => [
				'required',
				'string',
				'max:50',
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
			'activo' => [
				'nullable',
				'boolean',
			],
		];
	}
	public function messages(): array
	{
		return [
			'idUnidadMedida.required' => 'La unidad de medida es obligatoria.',
			'idUnidadMedida.integer' => 'El identificador de la unidad de medida no es válido.',
			'idUnidadMedida.exists' => 'La unidad de medida indicada no existe.',
			'descripcion.required' => 'La descripción del producto es obligatoria.',
			'descripcion.string' => 'La descripción debe ser texto.',
			'descripcion.max' => 'La descripción no puede superar los 300 caracteres.',
			'codigo.required' => 'El código del producto es obligatorio.',
			'codigo.string' => 'El código debe ser texto.',
			'codigo.max' => 'El código no puede superar los 50 caracteres.',
			'fechaRegistro.date' => 'La fecha de registro no es válida.',
			'idUsuarioRegistro.required' => 'El usuario de registro es obligatorio.',
			'idUsuarioRegistro.integer' => 'El identificador del usuario de registro no es válido.',
			'idUsuarioRegistro.exists' => 'El usuario de registro no existe.',
			'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
		];
	}
}
