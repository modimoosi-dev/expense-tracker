<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
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
            'category_id' => 'sometimes|required|exists:categories,id',
            'amount' => 'sometimes|required|numeric|min:0.01|max:9999999.99',
            'type' => 'sometimes|required|in:income,expense',
            'description' => 'nullable|string|max:1000',
            'date' => 'sometimes|required|date|before_or_equal:today',
            'payment_method' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Category is required',
            'category_id.exists' => 'The selected category does not exist',
            'amount.required' => 'Amount is required',
            'amount.min' => 'Amount must be at least 0.01',
            'amount.max' => 'Amount cannot exceed 9,999,999.99',
            'type.required' => 'Transaction type is required',
            'type.in' => 'Transaction type must be either income or expense',
            'date.required' => 'Date is required',
            'date.before_or_equal' => 'Date cannot be in the future',
        ];
    }
}
