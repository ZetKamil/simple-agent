<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'nullable|string|max:30',
            'company_vat' => 'nullable|string|max:50',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Naam is verplicht.',
            'email.required' => 'Email is verplicht.',
            'email.email' => 'Ongeldig email.',
            'email.unique' => 'Deze email bestaat in onze db.',
            'phone.max' => 'Geef juiste telefoonnummer.',
            'company_vat.max' => 'Niet langer dan 50 tekens..',
        ];
    }
}
