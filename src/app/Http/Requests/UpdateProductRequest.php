<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ProductCategoryEnum;
use App\Models\Product;
use Illuminate\Validation\Rule;

final class UpdateProductRequest extends PublicFormRequest
{
    public function authorize(): bool
    {
        $product = $this->route(param: 'product');

        return $product instanceof Product
            && ($this->user()?->can('update', $product) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'required', 'string', 'max:255'],
            'description'   => ['sometimes', 'nullable', 'string'],
            'price'         => ['sometimes', 'required', 'integer:strict', 'min:0', 'max:' . Product::PRICE_MAX],
            'weight'        => ['sometimes', 'required', 'integer:strict', 'min:0', 'max:' . Product::WEIGHT_MAX],
            'category'      => ['sometimes', 'required', 'string', Rule::enum(type: ProductCategoryEnum::class)],
        ];
    }
}
