<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FacturaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        $reglas = [
            'cliente_id' => ['required', 'exists:clientes,id'],
            'serie' => ['required', 'in:C,B'],
            'subtotal' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'iva_porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'irpf_porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'concepto' => ['required', 'string', 'max:500'],
            'fecha_evento' => ['nullable', 'date'],
            'fecha_emision' => ['required', 'date'],
        ];

        // Al editar, la serie no se puede cambiar
        if ($this->isMethod('PUT')) {
            unset($reglas['serie']);
        }

        return $reglas;
    }
}
