<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\Auth\DTO\LoginDTO;

final class LoginRequest extends PublicFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function toDTO(): LoginDTO
    {
        return LoginDTO::fromArray(data: $this->validated());
    }
}
