<?php
namespace App\Http\Requests\Almacen;
use Illuminate\Foundation\Http\FormRequest;
class StoreUnidadMedidaRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}
	public function rules(): array
	{
		return [
			'codigo' => [
				'required',
				'string',
				'max:50',
			],
			'descripcion' => [
				'required',
				'string',
				'max:300',
			],
			'factorBase' => [
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
			'activo' => [
				'nullable',
				'boolean',
			],
		];
	}
	public function messages(): array
	{
		return [
			'codigo.required' => 'El código de la unidad de medida es obligatorio.',
			'codigo.string' => 'El código debe ser texto.',
			'codigo.max' => 'El código no puede superar los 50 caracteres.',
			'descripcion.required' => 'La descripción de la unidad de medida es obligatoria.',
			'descripcion.string' => 'La descripción debe ser texto.',
			'descripcion.max' => 'La descripción no puede superar los 300 caracteres.',
			'factorBase.required' => 'El factor base es obligatorio.',
			'factorBase.numeric' => 'El factor base debe ser numérico.',
			'factorBase.gt' => 'El factor base debe ser mayor que cero.',
			'fechaRegistro.date' => 'La fecha de registro no es válida.',
			'idUsuarioRegistro.required' => 'El usuario de registro es obligatorio.',
			'idUsuarioRegistro.integer' => 'El identificador del usuario de registro no es válido.',
			'idUsuarioRegistro.exists' => 'El usuario de registro no existe.',
			'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
		];
	}
}
