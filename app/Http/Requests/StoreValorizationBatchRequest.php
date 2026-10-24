<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreValorizationBatchRequest extends FormRequest
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
            'batch_code' => ['required', 'string', 'max:50', 'unique:valorization_batches,batch_code'],
            'valorization_method_id' => ['required', 'exists:valorization_methods,id'],
            'start_date' => ['required', 'date'],
            'expected_harvest_date' => ['required', 'date', 'after_or_equal:start_date'],
            'actual_harvest_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'input_organic_kg' => ['required', 'numeric', 'min:0.01'],
            'estimated_output_kg' => ['nullable', 'numeric', 'min:0'],
            'actual_output_kg' => ['nullable', 'numeric', 'min:0'],
            'process_residue_kg' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:processing,harvested,cancelled'],
        ];
    }

    /**
     * Custom validation messages in Indonesian.
     */
    public function messages(): array
    {
        return [
            'batch_code.required' => 'Kode batch wajib diisi.',
            'batch_code.unique' => 'Kode batch ini sudah digunakan, gunakan kode lain.',
            'valorization_method_id.required' => 'Metode valorisasi wajib dipilih.',
            'valorization_method_id.exists' => 'Metode valorisasi yang dipilih tidak valid.',
            'start_date.required' => 'Tanggal mulai proses biokonversi wajib diisi.',
            'expected_harvest_date.required' => 'Perkiraan tanggal panen wajib diisi.',
            'expected_harvest_date.after_or_equal' => 'Perkiraan panen harus sama atau setelah tanggal mulai proses.',
            'actual_harvest_date.after_or_equal' => 'Tanggal panen aktual harus sama atau setelah tanggal mulai proses.',
            'input_organic_kg.required' => 'Berat input bahan organik (kg) wajib diisi.',
            'input_organic_kg.min' => 'Berat input bahan organik minimal 0.01 kg.',
            'status.required' => 'Status batch wajib ditentukan.',
            'status.in' => 'Status batch harus salah satu dari: Sedang Diproses, Sudah Dipanen, atau Dibatalkan.',
        ];
    }
}
