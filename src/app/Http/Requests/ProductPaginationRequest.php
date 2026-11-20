<?php

namespace App\Http\Requests;

class ProductPaginationRequest extends PublicFormRequest
{
    public function rules(): array
    {
        return [
            'page'  => ['integer', 'min:1', 'max:1000000'],
            'limit' => ['integer', 'min:1', 'max:100'],
        ];
    }
}
