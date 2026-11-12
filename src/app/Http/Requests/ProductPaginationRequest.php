<?php

namespace App\Http\Requests;

use App\Support\PaginationInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProductPaginationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation()
    {
        $this->merge([
            'page' => PaginationInput::normalize($this->query('page'), 1, 1_000_000),
            'limit' => PaginationInput::normalize($this->query('limit'), 5, 100),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page'  => 'integer|min:1|max:1000000',
            'limit' => 'integer|min:1|max:100',
        ];
    }
}
