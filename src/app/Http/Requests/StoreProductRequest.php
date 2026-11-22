<?php

namespace App\Http\Requests;

use App\Enums\ProductCategoryEnum;
use App\Models\Product;
use Illuminate\Validation\Rule;

class StoreProductRequest extends PublicFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'price'         => ['required', 'integer:strict', 'min:0', 'max:' . Product::PRICE_MAX],
            'weight'        => ['required', 'integer:strict', 'min:0', 'max:' . Product::WEIGHT_MAX],
            'category'      => ['required', 'string', Rule::enum(ProductCategoryEnum::class)],
        ];
    }
}
