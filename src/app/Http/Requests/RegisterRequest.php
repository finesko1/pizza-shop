<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\Auth\DTO\RegisterDTO;

final class RegisterRequest extends PublicFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function toDTO(): RegisterDTO
    {
        return RegisterDTO::fromArray(data: $this->validated());
    }
}
