<?php

declare(strict_types=1);

namespace App\Http\Requests;

final class ProductPaginationRequest extends PublicFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'page'  => ['integer', 'min:1', 'max:1000000'],
            'limit' => ['integer', 'min:1', 'max:100'],
        ];
    }
}
