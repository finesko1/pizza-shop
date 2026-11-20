<?php

namespace App\Http\Requests;

use App\Enums\ProductCategoryEnum;
use App\Models\Product;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends PublicFormRequest
{
    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'required', 'string', 'max:255'],
            'description'   => ['sometimes', 'nullable', 'string'],
            'price'         => ['sometimes', 'required', 'integer:strict', 'min:0', 'max:' . Product::PRICE_MAX],
            'weight'        => ['sometimes', 'required', 'integer:strict', 'min:0', 'max:' . Product::WEIGHT_MAX],
            'category'      => ['sometimes', 'required', 'string', Rule::enum(ProductCategoryEnum::class)],
        ];
    }
}
