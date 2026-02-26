<?php

namespace App\Http\Requests\Estimate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateEstimateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\Estimate $estimate */
        $estimate = $this->route('estimate');

        return $estimate->canEdit();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.lineable_type' => ['required', 'string', 'in:Part,LaborService'],
            'lines.*.lineable_id' => ['required', 'integer'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'],
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var array<int, array{lineable_type: string, lineable_id: int}> $lines */
                $lines = $this->input('lines', []);

                foreach ($lines as $index => $line) {
                    if (! isset($line['lineable_type'], $line['lineable_id'])) {
                        continue;
                    }

                    $table = $line['lineable_type'] === 'Part' ? 'parts' : 'labor_services';

                    if (! \Illuminate\Support\Facades\DB::table($table)->where('id', $line['lineable_id'])->exists()) {
                        $validator->errors()->add(
                            "lines.{$index}.lineable_id",
                            'The selected item does not exist.'
                        );
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lines.required' => 'At least one line item is required.',
            'lines.min' => 'At least one line item is required.',
        ];
    }
}
