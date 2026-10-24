<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreWasteLogRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'log_date' => ['required', 'date'],
            'collection_point_id' => ['required', 'exists:collection_points,id'],
            'commodity_id' => ['required', 'exists:commodities,id'],
            'total_supply_kg' => ['required', 'numeric', 'min:0.01'],
            'waste_weight_kg' => ['required', 'numeric', 'min:0', 'lte:total_supply_kg'],
            'organic_sortable_kg' => ['required', 'numeric', 'min:0', 'lte:waste_weight_kg'],
            'inorganic_residue_kg' => ['required', 'numeric', 'min:0', 'lte:waste_weight_kg'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Additional validator callback to enforce:
     * organic_sortable_kg + inorganic_residue_kg == waste_weight_kg (toleransi rounding 0.01)
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $waste = (float) $this->input('waste_weight_kg', 0);
            $organic = (float) $this->input('organic_sortable_kg', 0);
            $inorganic = (float) $this->input('inorganic_residue_kg', 0);

            if (abs(($organic + $inorganic) - $waste) > 0.01) {
                $validator->errors()->add(
                    'organic_sortable_kg',
                    'Jumlah fraksi organik layak olah (' . number_format($organic, 2, ',', '.') . ' kg) dan residu anorganik (' . number_format($inorganic, 2, ',', '.') . ' kg) harus tepat sama dengan total berat limbah (' . number_format($waste, 2, ',', '.') . ' kg).'
                );
            }
        });
    }

    /**
     * Custom validation messages in Indonesian.
     */
    public function messages(): array
    {
        return [
            'log_date.required' => 'Tanggal pencatatan limbah wajib diisi.',
            'log_date.date' => 'Format tanggal pencatatan tidak valid.',
            'collection_point_id.required' => 'Titik timbulan pengumpulan wajib dipilih.',
            'collection_point_id.exists' => 'Titik timbulan pengumpulan yang dipilih tidak valid.',
            'commodity_id.required' => 'Komoditas sayur wajib dipilih.',
            'commodity_id.exists' => 'Komoditas sayur yang dipilih tidak valid.',
            'total_supply_kg.required' => 'Total pasokan masuk (kg) wajib diisi.',
            'total_supply_kg.numeric' => 'Total pasokan masuk harus berupa angka numerik.',
            'total_supply_kg.min' => 'Total pasokan masuk minimal 0.01 kg.',
            'waste_weight_kg.required' => 'Berat limbah (kg) wajib diisi.',
            'waste_weight_kg.numeric' => 'Berat limbah harus berupa angka numerik.',
            'waste_weight_kg.min' => 'Berat limbah tidak boleh negatif.',
            'waste_weight_kg.lte' => 'Berat limbah tidak boleh melebihi total pasokan masuk (:value kg).',
            'organic_sortable_kg.required' => 'Berat organik layak olah (kg) wajib diisi.',
            'organic_sortable_kg.numeric' => 'Berat organik layak olah harus berupa angka numerik.',
            'organic_sortable_kg.lte' => 'Berat organik layak olah tidak boleh melebihi berat limbah.',
            'inorganic_residue_kg.required' => 'Berat residu anorganik (kg) wajib diisi.',
            'inorganic_residue_kg.numeric' => 'Berat residu anorganik harus berupa angka numerik.',
            'inorganic_residue_kg.lte' => 'Berat residu anorganik tidak boleh melebihi berat limbah.',
            'notes.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }
}
