<?php

namespace App\Http\Requests\Unit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('vin')) {
            $this->merge(['vin' => strtoupper((string) $this->vin)]);
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vin' => [
                'required',
                'string',
                'size:17',
                'regex:/^[A-HJ-NPR-Z0-9]{17}$/',
                Rule::unique('units', 'vin')->ignore($this->route('unit')),
            ],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'engine' => ['nullable', 'string', 'max:255'],
            'mileage' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vin.regex' => 'The VIN must be exactly 17 alphanumeric characters (excluding I, O, Q).',
            'vin.unique' => 'This VIN is already registered in the system.',
        ];
    }
}
