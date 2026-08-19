<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;

class StoreUsuarioRequest extends FormRequest
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
                'exists:empleado.persona,idPersona',
            ],
            'cuenta' => [
                'required',
                'string',
                'max:100',
            ],
            'passwordHash' => [
                'required',
                'string',
                'max:300',
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
            'idPersona.required' => 'La persona es obligatoria.',
            'idPersona.exists' => 'La persona indicada no existe.',
            'cuenta.required' => 'La cuenta es obligatoria.',
            'cuenta.max' => 'La cuenta no puede superar los 100 caracteres.',
            'passwordHash.required' => 'La contraseña es obligatoria.',
            'passwordHash.max' => 'La contraseña no puede superar los 300 caracteres.',
            'fechaRegistro.date' => 'La fecha de registro no es válida.',
            'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
        ];
    }
}
