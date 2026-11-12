<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
            'name'          => 'sometimes|required|string|max:255',
            'description'   => 'sometimes|nullable|string',
            'price'         => 'sometimes|required|integer|min:0|max:' . Product::PRICE_MAX,
            'weight'        => 'sometimes|required|integer|min:0|max:' . Product::WEIGHT_MAX,
            'category'      => 'sometimes|required|string|max:255',
        ];
    }
}
